<?php
namespace adevendorf\pretzelimage\services;

use adevendorf\pretzelimage\models\ImageModel;
use adevendorf\pretzelimage\models\TransformModel;
use Craft;
use craft\elements\Asset;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use yii\web\HttpException;

use adevendorf\pretzelimage\helpers\PretzelHelper;

class PretezelService
{
    /**
     * Returns fully pathed image urls to the image generation controller
     * @param Asset $asset
     * @param array $transforms
     * @param array $defaults
     *
     * @return array|string
     * @throws HttpException
     */
    public function url(Asset $asset, array $transforms = [], array $defaults = []): array|ImageModel
    {
        $images = [];

        // SVGs can't be transformed, so they always get the plain, untransformed URL
        $isSvg = PretzelHelper::isSvg($asset);

        if ($this->isMulti($transforms)) {
            foreach ($transforms as $transform) {
                $merged = $isSvg ? [] : PretzelHelper::mergeTransforms($transform, $defaults);
                $images[] = new ImageModel($asset, new TransformModel($asset, $merged));
            }
        } else {
            $merged = $isSvg ? [] : PretzelHelper::mergeTransforms($transforms, $defaults);
            $images = new ImageModel($asset, new TransformModel($asset, $merged));
        }

        return $images;
    }


    /**
     * Returns the system path to the newly generated image in the final folder location
     * These come from the controller
     * @param $id
     * @param $filename
     * @param $transforms
     * @param $ext
     *
     * @return object
     */
    public function generateImage($id, $filename, $transforms, $ext): object
    {
        $asset = Asset::find()->id($id)->kind('image')->one();

        // SVGs are served by the copy action; they can't be transformed
        if (!$asset || PretzelHelper::isSvg($asset)) {
            throw new HttpException(404, 'File Not Found');
        }

        $transformModel = new TransformModel($asset, $transforms);

        $imageModel = new ImageModel($asset, $transformModel);

        $manager = new ImageManager($this->driver());

        // Intervention 4 auto-orients using EXIF on decode
        $image = $manager->decodeBinary($asset->getContents());

        if ($transformModel->background()) {
            // flatten transparent areas onto the background color
            $image->fillTransparentAreas($transformModel->background());
        }

        $final = PretzelHelper::ensureDimensions($transformModel->getTransforms(), $asset);

        $image = $this->runCropResize($image, $final['width'], $final['height'], $final['position'] ?? '50-50');

        return (object) [
            'asset' => $asset,
            'image' => $image,
            'path' => $imageModel->getPath(),
            'transforms' => $final,
            'quality' => $transformModel->quality(),
        ];
    }


    /**
     * Imagick when available, otherwise GD. Override with PRETZEL_DRIVER=gd|imagick.
     */
    private function driver(): GdDriver|ImagickDriver
    {
        $preferred = strtolower((string) \craft\helpers\App::env('PRETZEL_DRIVER'));

        if ($preferred === 'gd' || ($preferred !== 'imagick' && !extension_loaded('imagick'))) {
            return new GdDriver();
        }

        return new ImagickDriver();
    }


    /**
     * Modified from Glide's CropResize from https://github.com/thephpleague/glide
     * Copyright (c) 2015 Jonathan Reinink <jonathan@reinink.ca>
     */
    public function runCropResize(ImageInterface $image, $width, $height, $position): ImageInterface
    {
        [$offset_percentage_x, $offset_percentage_y] = $this->getCrop($position);

        $width = min((int) $width, $image->width());
        $height = min((int) $height, $image->height());

        $resize_width = $width;
        $resize_height = $width * ($image->height() / $image->width());

        if ($height > $resize_height) {
            $resize_width = $height * ($image->width() / $image->height());
            $resize_height = $height;
        }

        $image->resize((int) ceil($resize_width), (int) ceil($resize_height));

        $max_offset_x = max(0, $image->width() - $width);
        $max_offset_y = max(0, $image->height() - $height);

        $offset_x = (int) (($image->width() * $offset_percentage_x / 100) - ($width / 2));
        $offset_y = (int) (($image->height() * $offset_percentage_y / 100) - ($height / 2));

        $offset_x = max(0, min($offset_x, $max_offset_x));
        $offset_y = max(0, min($offset_y, $max_offset_y));

        return $image->crop($width, $height, $offset_x, $offset_y);
    }


    public function getCrop(string $position = '50-50'): array
    {
        $pos = array_pad(explode('-', $position), 2, 50);

        if ($pos[0] < 0) $pos[0] = 0;
        if ($pos[0] > 100) $pos[0] = 100;

        if ($pos[1] < 0) $pos[1] = 0;
        if ($pos[1] > 100) $pos[1] = 100;

        return [(float) $pos[0], (float) $pos[1]];
    }


    private function isMulti(array $array):bool
    {
        return $array !== [] && is_array(reset($array));
    }
}