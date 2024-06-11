<?php
namespace adevendorf\pretzelimage\helpers;

use adevendorf\pretzelimage\models\TransformModel;
use adevendorf\pretzelimage\Plugin;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use adevendorf\pretzelimage\helpers\TransformationHelper;

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


    public static function makeFilename(Asset $image, TransformModel $transform): string
    {
        $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
        $filename = pathinfo($image->filename, PATHINFO_FILENAME);

        $isSvg = str_contains($image->getMimetype(), 'svg');

        if ($isSvg) {
            $extension = 'svg';
        }

        if ($transform->format()) {
            $extension = $transform->format();
        }

//        if ($transform->position() && gettype($transform->position()) === 'array') {
//            $transforms->setPosition = implode('-', [
//                (str_replace('.', '-', number_format($transforms['position']['x'], 1))),
//                (str_replace('.', '-', number_format($transforms['position']['y'], 1)))
//            ]);
//        }

        return implode([
            "{$filename}",
            ((($isSvg && $transform->format()) || !$isSvg) ? "~" : ''),
            $transform->toTransformString(),
            ".{$extension}"
        ]);
    }

    public static function additionalFolderPaths($id): string
    {
        $md5String = md5("image_{$id}");
        $md5a = substr($md5String, 0, 2);

        return $md5a . "/{$id}/";
    }

    public static function folderPath(int|string $id): string
    {
        $webPath = getEnv('PRETZEL_PATH') ?: Plugin::DEFAULT_PATH;

        return '/' . $webPath . '/' . self::additionalFolderPaths($id);
    }


    public static function webPath(int|string $id): string
    {
        return PretzelSettingHelper::webPathHost() . self::folderPath($id);
    }


    public static function saveImage(Asset $image, $imageIntervention, $newFilePath, $format = 'jpg', $quality = 90)
    {
        if (!is_dir(Craft::getAlias('@root') . '/web' . self::folderPath($image->id))) {
            FileHelper::createDirectory(Craft::getAlias('@root') . '/web' . self::folderPath($image->id));
        }
        dd(Craft::getAlias('@root') . '/web' . self::folderPath($image->id));

        $imageIntervention->save(Craft::getAlias('@root') . '/web' . $newFilePath, $quality);

        return Craft::getAlias('@root') . '/web' . $newFilePath;
    }

    public static function ensureDimensions(array $t, Asset $asset): array
    {
        $ratio = $asset->getWidth() / $asset->getHeight();

        if (isset($t['width']) && !isset($t['height'])) {
            if ($t['width'] > $asset->getWidth()) {
                $t['width'] = $asset->getWidth();
            }

            if ($ratio >= 1) {
                $t['height'] = intval($t['width'] / floatval($ratio));
            } else {
                $t['height'] = intval($t['width'] * floatval($ratio));
            }
        }

        if (isset($t['height']) && !isset($t['width'])) {
            if ($t['height'] > $asset->getHeight()) {
                $t['height'] = $asset->getHeight();
            }

            if ($ratio > 1) {
                $t['width'] = intval($t['height'] * floatval($ratio));
            } else {
                $t['width'] = intval($t['height'] / floatval($ratio));
            }
        }

        if (!isset($t['height']) && !isset($t['width'])) {
            $t['height'] = $asset->getHeight();
            $t['width'] = $asset->getWidth();
        }

        return $t;
    }
}