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
    protected $allowAnonymous = ['copy', 'generate'];

    public function actionCopy($md5, $id, $filename, $ext): bool
    {
        if (!$id) {
            throw new HttpException(404, 'File Not Found');
        }

        $asset = Asset::find()->id($id)->one();

        if (!$asset) {
            throw new HttpException(404, 'File Not Found');
        }

        // $referrer = parse_url(Craft::$app->getRequest()->getReferrer(), PHP_URL_HOST);

        // if (!PretzelSettingHelper::isValidHost($referrer)) {
        //     throw new HttpException(403, 'Unable to process request');
        // }

        $path = PretzelHelper::folderPath($asset->id) . $filename . $ext;

        if (!is_dir(Craft::getAlias('@webroot') . PretzelHelper::folderPath($asset->id))) {
            FileHelper::createDirectory(Craft::getAlias('@webroot') . PretzelHelper::folderPath($asset->id));
        }

        rename($asset->getCopyOfFile(), Craft::getAlias('@webroot') . $path);

        sleep(0.25);

        $fp = fopen(Craft::getAlias('@webroot') . $path, 'rb');

        http_response_code(200);

        header('Content-Type: ' . mime_content_type(Craft::getAlias('@webroot') . $path));
        header('Content-Length: ' . filesize(Craft::getAlias('@webroot') . $path));
        header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');

        fpassthru($fp);

        exit;
    }

    /**
     * @throws HttpException
     */
    public function actionGenerate($md5, $id, $filename, $transforms, $ext): void
    {
        if (!$id) {
            throw new HttpException(404, 'File Not Found');
        }


//        $unique = base64_encode(random_bytes(10));
//        $logFile = Craft::getAlias('@storage') . '/logs/pretzel.log';
//        $log = $unique .': '.  $id .' - '. $filename .' - '. $transforms .' - '. $ext."\n";
//        \craft\helpers\FileHelper::writeToFile($logFile, $log, ['append' => true]);


        // $referrer = parse_url(Craft::$app->getRequest()->getReferrer(), PHP_URL_HOST);

        // if (!PretzelSettingHelper::isValidHost($referrer)) {
        //     throw new HttpException(403, 'Unable to process request');
        // }

        $imageData = Plugin::$plugin->pretzelService->generateImage($id, $filename, $transforms, $ext);

        $path = PretzelHelper::saveImage(
            $imageData->asset,
            $imageData->image,
            $imageData->path,
            $ext,
            $imageData->quality,
        );

//        $log = $unique .': '.  $path ."\n";
//        \craft\helpers\FileHelper::writeToFile($logFile, $log, ['append' => true]);

        sleep(0.25);

        $fp = fopen($path, 'rb');

        http_response_code(200);

        header('Content-Type: ' . mime_content_type($path));
        header('Content-Length: ' . filesize($path));
        header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');

        fpassthru($fp);

        exit;
    }
}