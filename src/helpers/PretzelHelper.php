<?php
namespace adevendorf\pretzelimage\helpers;

use adevendorf\pretzelimage\Plugin;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;

class PretzelHelper
{
    const TRANSFORM_MAPPINGS = [
        'width' => 'W',
        'height' => 'H',
        'position' => 'P',
        'quality' => 'Q',
        'mode' => 'M',
        'format' => 'F',
        'ratio' => 'R',
    ];



    public static function convertTransformsToFilename(Asset $image, $transform = [], $defaults = []): string
    {
        $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
        $filename = pathinfo($image->filename, PATHINFO_FILENAME);

        if (isset($transform['format'])) {
            $extension = $transform['format'];
        }

        if (isset($transform['position']) && gettype($transform['position']) === 'array') {
            $transform['position'] = (str_replace('.','-',number_format($transform['position']['x'],1))) .'-'. (str_replace('.', '-', number_format($transform['position']['y'],1)));
        }

        return implode([
            "{$filename}~",
            self::transformsToString($transform, $defaults),
            ".{$extension}"
        ]);
    }

    private static function transformsToString(array $transform, array $defaults = []): string
    {
        $obj = [];

        if (isset($defaults['ratio'])) {
            if (isset($defaults['width']) && !isset($defaults['height'])) {
                if ($defaults['ratio'] > 1) {
                    $defaults['height'] = intval($defaults['width'] / floatval($defaults['ratio']));
                } else {
                    $defaults['height'] = intval($defaults['width'] * floatval($defaults['ratio']));
                }
            }

            if (isset($defaults['height']) && !isset($defaults['width'])) {
                if ($defaults['ratio'] > 1) {
                    $defaults['width'] = intval($defaults['height'] * floatval($defaults['ratio']));
                } else {
                    $defaults['width'] = intval($defaults['height'] / floatval($defaults['ratio']));
                }
            }

            unset($defaults['ratio']);
        }


        if (isset($transform['ratio'])) {
            if (isset($transform['width']) && !isset($transform['height'])) {
                if ($transform['ratio'] > 1) {
                    $transform['height'] = intval($transform['width'] / floatval($transform['ratio']));
                } else {
                    $transform['height'] = intval($transform['width'] * floatval($transform['ratio']));
                }
            }

            if (isset($transform['height']) && !isset($transform['width'])) {
                if ($transform['ratio'] > 1) {
                    $transform['width'] = intval($transform['height'] * floatval($transform['ratio']));
                } else {
                    $transform['width'] = intval($transform['height'] / floatval($transform['ratio']));
                }
            }

            unset($transform['ratio']);
        }

        foreach ($defaults as $key => $value) {
            if (isset($obj[$key])) {
                $obj[$key] = self::TRANSFORM_MAPPINGS[$key] . $value;
            } else {
                $obj[] = self::TRANSFORM_MAPPINGS[$key] . $value;
            }
        }

        foreach ($transform as $key => $value) {
            if (isset($obj[$key])) {
                $obj[$key] = self::TRANSFORM_MAPPINGS[$key] . $value;
            } else {
                $obj[] = self::TRANSFORM_MAPPINGS[$key] . $value;
            }
        }

        sort($obj);

        return implode('_', $obj);
    }

    public static function convertTransformStringToArray(string $string): array
    {
        $transforms = [
            'position' => '50-50',
        ];

        $options = explode('_', $string);

        foreach($options as $option) {
            switch (substr($option, 0, 1)) {
                case 'W':
                    $transforms['width'] = intval(substr($option, 1));
                    break;
                case 'H':
                    $transforms['height'] = intval(substr($option, 1));
                    break;
                case "P":
                    $value = explode('-', substr($option, 1));
                    $transforms['position'] = $value[0] .'.'. $value[1] .'-'. $value[2] .'.'. $value[3];
                    break;
                case 'Q':
                    $transforms['quality'] = intval(substr($option, 1));
                    break;
                case 'M':
                    $transforms['mode'] = substr($option, 1);
                    break;
                case 'F':
                    $transforms['format'] = substr($option, 1);
                    break;
            }
        }

        ksort($transforms);

        return $transforms;
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


    public static function saveImage(Asset $image, $imageIntervention, $new, $format = 'jpg', $quality = 90)
    {
        if (!is_dir(Craft::getAlias('@webroot') . self::folderPath($image->id))) {
            FileHelper::createDirectory(Craft::getAlias('@webroot') . self::folderPath($image->id));
        }

        $imageIntervention->save(Craft::getAlias('@webroot') .$new);

        return Craft::getAlias('@webroot') . $new;
    }

    public static function ensureDimensions(array $t, Asset $asset)
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