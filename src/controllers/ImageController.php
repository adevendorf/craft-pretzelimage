<?php
namespace adevendorf\pretzelimage\controllers;

use adevendorf\pretzelimage\helpers\PretzelHelper;
use adevendorf\pretzelimage\helpers\PretzelSettingHelper;
use Craft;

use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\web\Controller;
use yii\web\HttpException;

use adevendorf\pretzelimage\Plugin;
use GuzzleHttp\Psr7\Response;

/**
 * Class ImageController
 */
class ImageController extends Controller
{
    protected array|bool|int $allowAnonymous = ['copy', 'generate'];

    public function actionCopy($md5, $id, $filename, $ext): bool
    {
        if (!$id) {
            throw new HttpException(404, 'File Not Found');
        }

        $asset = Asset::find()->id($id)->one();

        if (!$asset) {
            throw new HttpException(404, 'File Not Found');
        }

        $referrer = parse_url(Craft::$app->getRequest()->getReferrer(), PHP_URL_HOST);

        if (!PretzelSettingHelper::isValidHost($referrer)) {
            throw new HttpException(403, 'Unable to process request');
        }

        $path = PretzelHelper::folderPath($asset->id) . $filename . $ext;

        if (!is_dir(Craft::getAlias('@webroot') . PretzelHelper::folderPath($asset->id))) {
            FileHelper::createDirectory(Craft::getAlias('@webroot') . PretzelHelper::folderPath($asset->id));
        }

        rename($asset->getCopyOfFile(), Craft::getAlias('@webroot') . $path);

        $fp = fopen(Craft::getAlias('@webroot') . $path, 'rb');

        http_response_code(200);

        header('Content-Type: ' . mime_content_type(Craft::getAlias('@webroot') . $path));
        header('Content-Length: ' . filesize(Craft::getAlias('@webroot') . $path));

        fpassthru($fp);

        return true;
    }

    /**
     * @throws HttpException
     */
    public function actionGenerate($md5, $id, $filename, $transforms, $ext): void
    {
        if (!$id) {
            throw new HttpException(404, 'File Not Found');
        }

        $referrer = parse_url(Craft::$app->getRequest()->getReferrer(), PHP_URL_HOST);

        if (!PretzelSettingHelper::isValidHost($referrer)) {
            throw new HttpException(403, 'Unable to process request');
        }

        $imageData = Plugin::$plugin->pretzelService->generateImage($id, $filename, $transforms, $ext);

        $path = PretzelHelper::saveImage(
            $imageData->asset,
            $imageData->image,
            $imageData->path,
            $ext,
            $imageData->quality,
        );

        sleep(0.1);

        $fp = fopen($path, 'rb');

        http_response_code(200);

        header('Content-Type: ' . $imageData->image->mime(), true);
        header('Content-Length: ' . filesize($path), true);

        fpassthru($fp);

        exit;
    }
}