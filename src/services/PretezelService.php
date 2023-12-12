<?php
namespace adevendorf\pretzelimage\services;

use craft\helpers\ImageTransforms;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use yii\web\HttpException;

use Craft;
use craft\elements\Asset;
use craft\helpers\ImageTransforms as TransformHelper;
use craft\imagetransforms\ImageTransformer;

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
    public function url(Asset $asset, array $transforms = [], array $defaults = []): array|string
    {
        $transformedImages = [];

        foreach ($transforms as $key => $value) {
            if (gettype($key) !== 'string') {
                $transform = $value;
                $returnArray = true;
            } else {
                $transform = $transforms;
                $returnArray = false;
            }

            if (isset($transform['position'])) {
                if (gettype($transform['position']) === 'string' && preg_match('/(\d+)% (\d+)%/', $transform['position'])) {
                    preg_match_all('/(\d+)% (\d+)%/', $transform['position'], $matches);

                    $transform['position'] = [
                        'x' => $matches[1][0] / 100,
                        'y' => $matches[2][0] / 100
                    ];
                }

                $transform['position']['x'] = $transform['position']['x'] * 100;
                $transform['position']['y'] = $transform['position']['y'] * 100;
            }

            $transformedImages[] = PretzelHelper::webPath($asset->id) . PretzelHelper::convertTransformsToFilename($asset, $transform, $defaults);
        }

        return $returnArray ? $transformedImages : $transformedImages[0];
    }

    /**
     * Returns the system path to the newly generated image in the final folder location
     * These come from the controller
     * @param $id
     * @param $filename
     * @param $transforms
     * @param $ext
     *
     * @return string
     * @throws \craft\errors\ImageTransformException
     * @throws \yii\base\InvalidConfigException
     */
    public function generateImage($id, $filename, $transforms, $ext): object
    {
        $asset = Asset::find()->id($id)->one();

        $transformations = PretzelHelper::convertTransformStringToArray($transforms);

        $filename = PretzelHelper::convertTransformsToFilename($asset, $transformations);
        $transformedPath = PretzelHelper::folderPath($id) . $filename;

        $manager = new ImageManager();

        $image = $manager->make($asset->getCopyOfFile());

        $finalTransformations = PretzelHelper::ensureDimensions($transformations, $asset);

        $image = $this->runCropResize($image, $finalTransformations['width'], $finalTransformations['height'], $finalTransformations['position']);

        return (object) [
            'asset' => $asset,
            'image' => $image,
            'path' => $transformedPath,
            'transforms' => $finalTransformations,
        ];
    }


    /**
     * Modified from Glide's CropResize from https://github.com/thephpleague/glide
     * Copyright (c) 2015 Jonathan Reinink <jonathan@reinink.ca>
     */
    public function runCropResize(Image $image, $width, $height, $position)
    {
        [$offset_percentage_x, $offset_percentage_y] = $this->getCrop($position);

        if ($width > $image->getWidth()) $width = $image->getWidth();
        if ($height > $image->getHeight()) $height = $image->getHeight();

        $resize_width = $width;
        $resize_height = $width * ($image->height() / $image->width());

        if ($height > $resize_height) {
            $resize_width = $height * ($image->width() / $image->height());
            $resize_height = $height;
        }

        $image->resize($resize_width, $resize_height, function ($constraint) {
            $constraint->aspectRatio();
        });

        $offset_x = (int) (($image->width() * $offset_percentage_x / 100) - ($width / 2));
        $offset_y = (int) (($image->height() * $offset_percentage_y / 100) - ($height / 2));

        $max_offset_x = $image->width() - $width;
        $max_offset_y = $image->height() - $height;

        if ($offset_x < 0) {
            $offset_x = 0;
        }

        if ($offset_y < 0) {
            $offset_y = 0;
        }

        if ($offset_x > $max_offset_x) {
            $offset_x = $max_offset_x;
        }

        if ($offset_y > $max_offset_y) {
            $offset_y = $max_offset_y;
        }

        return $image->crop(
            $width,
            $height,
            $offset_x,
            $offset_y
        );
    }

    public function getCrop(string $position = '50-50'): array
    {
        $pos = explode('-', $position);

        if ($pos[0] < 0) $pos[0] = 0;
        if ($pos[0] > 100) $pos[0] = 100;

        if ($pos[1] < 0) $pos[1] = 0;
        if ($pos[1] > 100) $pos[1] = 100;

        return [$pos[0], $pos[1]];
    }
}