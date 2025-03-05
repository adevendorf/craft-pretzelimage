<?php
namespace adevendorf\pretzelimage\helpers;

use yii\web\HttpException;

class TransformationHelper
{
    const TRANSFORM_MAPPINGS = [
        'width' => 'W',
        'height' => 'H',
        'position' => 'P',
        'quality' => 'Q',
        'mode' => 'M',
        'format' => 'F',
        'ratio' => 'R',
        'background' => 'B',
    ];

    public static function isHex($value)
    {
        return ctype_xdigit($value);
    }

    public static function isValidFormat($value)
    {
        $valid = ['JPG', 'PNG', 'WEBP'];

        return in_array(strtoupper($value), $valid);
    }

    public static function validate($t)
    {
        if (isset($t['format'])) {
            if (!self::isValidFormat($t['format'])) {
                throw new HttpException(500, 'Invalid Format Transformation');
            }
        }

        if (isset($t['background'])) {
            if (!self::isHex($t['background'])) {
                throw new HttpException(500, 'Invalid Background Transformation');
            }
        }
    }


    public static function cleanTransformationArray(array $t)
    {
        if (isset($t['position'])) {
            if (
                gettype($t['position']) === 'string' &&
                preg_match('/(\d+)% (\d+)%/', $t['position'])
            ) {

                preg_match_all('/(\d+)% (\d+)%/', $t['position'], $matches);

                $t['position'] = [
                    'x' => $matches[1][0] / 100,
                    'y' => $matches[2][0] / 100
                ];
            }

            $t['position']['x'] = $t['position']['x'] * 100;
            $t['position']['y'] = $t['position']['y'] * 100;
        }

        if (isset($t['ratio'])) {
            // ignore ratio if height & width are provided
            if (isset($t['width']) && isset($t['height'])) {
                unset($t['ratio']);
            }
 ;

            // set height if width and ratio are provided
            if (isset($t['width']) && !isset($t['height'])) {
                if ($t['ratio'] == 1) {
                    $t['height'] = $t['width'];
                } else {
                    $t['height'] = intval($t['width'] / floatval($t['ratio']));
                }

                unset($t['ratio']);
            }


            // set width if height and ratio are provided
            if (isset($t['height']) && !isset($t['width'])) {
                if ($t['ratio'] == 1) {
                    $t['width'] = $t['height'];
                } else {
                    $t['width'] = intval($t['height'] * floatval($t['ratio']));
                }

                unset($t['ratio']);
            }
        }

        self::validate($t);

        return $t;
    }

    public static function convertStringToArray($string)
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
                    $transforms['position'] = self::formatPosition(substr($option, 1));
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
                case 'B':
                    $transforms['background'] = substr($option, 1);
                    break;
            }
        }

        ksort($transforms);

        self::validate($transforms);

        return $transforms;
    }

    public static function convertArrayToString($transform): string
    {
        $obj = [];

        foreach ($transform as $key => $value) {
            if ($key === 'position' && is_array($value)) {
                $value = round($value['x']) . '-' . round($value['y']);
            }

            if (isset(self::TRANSFORM_MAPPINGS[$key])) {
                if (isset($obj[$key])) {
                    $obj[$key] = self::TRANSFORM_MAPPINGS[$key] . $value;
                } else {
                    $obj[] = self::TRANSFORM_MAPPINGS[$key] . $value;
                }
            }
        }

        sort($obj);

        return implode('_', $obj);
    }


    public static function formatPosition($value)
    {
        $asArray = explode('-', $value);

        if (count($asArray) == 4) {
            $position = $asArray[0] . '.' .$asArray[1] . '-' .$asArray[2] . '.' .$asArray[3];
        }  elseif(count($asArray) == 3) {
            $position = $asArray[0] . '.' .$asArray[1] . '-' .$asArray[2];
        } elseif(count($asArray) == 2) {
            $position = $asArray[0] . '-' .$asArray[1];
        }


        return str_replace(".", "-", $position);
    }
}