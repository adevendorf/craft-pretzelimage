<?php
namespace adevendorf\pretzelimage\controllers;

use adevendorf\pretzelimage\helpers\PretzelHelper;
use adevendorf\pretzelimage\helpers\PretzelSettingHelper;
use Craft;

use craft\web\Controller;
use yii\web\HttpException;

use adevendorf\pretzelimage\Plugin;
use GuzzleHttp\Psr7\Response;

/**
 * Class ImageController
 */
class ImageController extends Controller
{
    protected $allowAnonymous = ['generate'];

    /**
     * @throws HttpException
     */
    public function actionGenerate($md5, $id, $filename, $transforms, $ext): Response
    {
        if (!$id) {
            throw new HttpException(404, 'File Not Found');
        }

        $referrer = parse_url(Craft::$app->getRequest()->getReferrer(), PHP_URL_HOST);

        if (!PretzelSettingHelper::isValidHost($referrer)) {
            throw new HttpException(403, 'Unable to process request');
        }

        $imageData = Plugin::$plugin->pretzelService->generateImage($id, $filename, $transforms, $ext);

        if (PretzelSettingHelper::shouldSaveImage()) {
            PretzelHelper::saveImage(
                $imageData->asset,
                $imageData->image,
                $imageData->path,
                $ext,
                isset($imageData->transforms['quality']) ? $imageData->transforms['quality'] : 90
            );
        }

        return $imageData->image->psrResponse(
            substr($ext, 1),
            isset($imageData->transforms['quality']) ? $imageData->transforms['quality'] : 90
        );


//        $fp = fopen($path, 'rb');
//
//        http_response_code(200);
//
//        header('Content-Type: ' . mime_content_type($path));
//        header('Content-Length: ' . filesize($path));
//
//        fpassthru($fp);
//
//        exit;
    }
}