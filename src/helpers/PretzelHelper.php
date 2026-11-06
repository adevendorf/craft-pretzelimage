<?php
namespace adevendorf\pretzelimage\helpers;

use adevendorf\pretzelimage\models\TransformModel;
use adevendorf\pretzelimage\Plugin;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use Intervention\Image\Encoders\{AvifEncoder, GifEncoder, JpegEncoder, PngEncoder, WebpEncoder};
use Intervention\Image\Interfaces\ImageInterface;

class PretzelHelper
{
    public static function mergeTransforms($transforms, $defaults)
    {
        $arr = [];

        foreach($defaults as $key => $value) {
            $arr[$key] = $value;
        }

        foreach($transforms as $key => $value) {
            $arr[$key] = $value;
        }

        return $arr;
    }


    public static function isSvg(Asset $asset): bool
    {
        return strtolower((string) $asset->getExtension()) === 'svg';
    }


    public static function makeFilename(Asset $image, TransformModel $transform): string
    {
        $filename = pathinfo($image->filename, PATHINFO_FILENAME);

        // SVGs can't be transformed: always the plain filename, no "~transforms" segment
        if (self::isSvg($image)) {
            return "{$filename}.svg";
        }

        $extension = strtolower($transform->format() ?: pathinfo($image->filename, PATHINFO_EXTENSION));

        return $filename . '~' . $transform->toTransformString() . ".{$extension}";
    }

    public static function additionalFolderPaths($id): string
    {
        $md5String = md5("image_{$id}");
        $md5a = substr($md5String, 0, 2);

        return $md5a . "/{$id}/";
    }

    public static function folderPath(int|string $id): string
    {
        $webPath = PretzelSettingHelper::imagePath();

        return '/' . $webPath . '/' . self::additionalFolderPaths($id);
    }


    public static function webPath(int|string $id): string
    {
        return PretzelSettingHelper::webPathHost() . self::folderPath($id);
    }


    /**
     * Encodes the image in the format of $newFilePath's extension and writes it under the webroot.
     */
    public static function saveImage(Asset $image, ImageInterface $imageIntervention, string $newFilePath, int $quality = 90): string
    {
        $fullPath = Craft::getAlias('@webroot') . $newFilePath;

        FileHelper::createDirectory(dirname($fullPath));

        $encoded = match (strtolower(pathinfo($newFilePath, PATHINFO_EXTENSION))) {
            'png' => $imageIntervention->encode(new PngEncoder()),
            'webp' => $imageIntervention->encode(new WebpEncoder(quality: $quality)),
            'avif' => $imageIntervention->encode(new AvifEncoder(quality: $quality)),
            'gif' => $imageIntervention->encode(new GifEncoder()),
            default => $imageIntervention->encode(new JpegEncoder(quality: $quality)),
        };

        // write to a temp file first so a concurrent request never serves a partial image
        $tmp = $fullPath . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, (string) $encoded);
        rename($tmp, $fullPath);

        return $fullPath;
    }

    public static function ensureDimensions(array $t, Asset $asset): array
    {
        $assetWidth = (int) $asset->getWidth();
        $assetHeight = (int) $asset->getHeight();

        if (!$assetWidth || !$assetHeight) {
            throw new \yii\web\HttpException(404, 'File Not Found');
        }

        $ratio = $assetWidth / $assetHeight;

        if (isset($t['width']) && !isset($t['height'])) {
            $t['width'] = min($t['width'], $assetWidth);
            $t['height'] = max(1, intval($t['width'] / $ratio));
        }

        if (isset($t['height']) && !isset($t['width'])) {
            $t['height'] = min($t['height'], $assetHeight);
            $t['width'] = max(1, intval($t['height'] * $ratio));
        }

        if (!isset($t['height']) && !isset($t['width'])) {
            $t['height'] = $assetHeight;
            $t['width'] = $assetWidth;
        }

        return $t;
    }
}
