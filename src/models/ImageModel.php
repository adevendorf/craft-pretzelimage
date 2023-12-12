<?php
namespace adevendorf\pretzelimage\models;

use adevendorf\pretzelimage\helpers\PretzelHelper;
use craft\elements\Asset;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

class ImageModel
{
    protected $asset;
    private $transform;
    private $defaults;

    public $filename;
    public $url;
    public $extension;

    public function __construct(Asset $asset, array $transform, array $defaults)
    {
        $this->asset = $asset;
        $this->transform = $transform;
        $this->defaults = $defaults;

        $this->setValues();
    }

    public function __toString() {
        return $this->getUrl();
    }

    private function setValues()
    {
        if (isset($this->transform['position'])) {
            if (gettype($this->transform['position']) === 'string' && preg_match('/(\d+)% (\d+)%/', $this->transform['position'])) {
                preg_match_all('/(\d+)% (\d+)%/', $this->transform['position'], $matches);

                $this->transform['position'] = [
                    'x' => $matches[1][0] / 100,
                    'y' => $matches[2][0] / 100
                ];
            }

            $this->transform['position']['x'] = $this->transform['position']['x'] * 100;
            $this->transform['position']['y'] = $this->transform['position']['y'] * 100;
        }

        $this->extension = $this->asset->getExtension();
        $this->filename = PretzelHelper::convertTransformsToFilename($this->asset, $this->transform, $this->defaults);
        $this->url = PretzelHelper::webPath($this->asset->id) . PretzelHelper::convertTransformsToFilename($this->asset, $this->transform, $this->defaults);
    }


    public function getFilename()
    {
        return $this->filename;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getExtension()
    {
        return $this->extension;
    }

}
