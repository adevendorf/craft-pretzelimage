<?php
namespace adevendorf\pretzelimage\controllers;

use adevendorf\pretzelimage\helpers\PretzelHelper;
use adevendorf\pretzelimage\models\ImageModel;
use adevendorf\pretzelimage\models\TransformModel;
use adevendorf\pretzelimage\Plugin;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\web\Controller;
use craft\web\Response;
use yii\web\HttpException;

/**
 * Class ImageController
 */
class ImageController extends Controller
{
    protected array|bool|int $allowAnonymous = ['copy', 'generate'];

    /**
     * Copies an SVG, unmodified, to the public folder. SVGs can't be transformed.
     *
     * @throws HttpException
     */
    public function actionCopy(string $md5, int $id, string $filename, string $ext): Response
    {
        $asset = Asset::find()->id($id)->kind('image')->one();

        if (!$asset || !PretzelHelper::isSvg($asset) || !$this->isValidFolder($md5, $id)) {
            throw new HttpException(404, 'File Not Found');
        }

        $imageModel = new ImageModel($asset, new TransformModel($asset, []));

        // the requested URL must be the one we would have generated for this asset
        if ($filename . $ext !== $imageModel->getFilename()) {
            throw new HttpException(404, 'File Not Found');
        }

        $fullPath = Craft::getAlias('@webroot') . $imageModel->getPath();

        FileHelper::createDirectory(dirname($fullPath));

        // write to a temp file first so a concurrent request never serves a partial file
        $tmp = $fullPath . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, $asset->getContents());
        rename($tmp, $fullPath);

        return $this->sendImage($fullPath, 'image/svg+xml');
    }

    /**
     * @throws HttpException
     */
    public function actionGenerate(string $md5, int $id, string $filename, string $transforms, string $ext): Response
    {
        if (!$this->isValidFolder($md5, $id)) {
            throw new HttpException(404, 'File Not Found');
        }

        $imageData = Plugin::$plugin->pretzelService->generateImage($id, $filename, $transforms, $ext);

        if ($filename !== pathinfo($imageData->asset->filename, PATHINFO_FILENAME)) {
            throw new HttpException(404, 'File Not Found');
        }

        // Save under exactly the requested URL (not the re-normalised transform string), so the
        // web server finds the static file on the next request.
        $path = PretzelHelper::saveImage(
            $imageData->asset,
            $imageData->image,
            PretzelHelper::folderPath($id) . $filename . '~' . $transforms . strtolower($ext),
            $imageData->quality,
        );

        return $this->sendImage($path, FileHelper::getMimeType($path) ?? 'application/octet-stream');
    }

    private function isValidFolder(string $md5, int $id): bool
    {
        return $md5 === substr(md5("image_{$id}"), 0, 2);
    }

    /**
     * Streams the freshly written file. Later requests are served by the web server directly.
     */
    private function sendImage(string $path, string $mimeType): Response
    {
        $response = Craft::$app->getResponse();
        $response->setCacheHeaders();

        return $response->sendFile($path, basename($path), [
            'mimeType' => $mimeType,
            'inline' => true,
        ]);
    }
}
