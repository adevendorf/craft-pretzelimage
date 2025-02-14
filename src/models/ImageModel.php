<?php
namespace adevendorf\pretzelimage\models;


use craft\elements\Asset;
use adevendorf\pretzelimage\models\TransformModel;
use adevendorf\pretzelimage\helpers\PretzelHelper;

class ImageModel
{
    public $asset;
    public $filename;
    public $url;
    public $extension;
    public $path;
    public $transform;
    public $quality;

    public function __construct(Asset $asset, TransformModel $model)
    {
        $this->asset = $asset;
        $this->transform = $model;
        $this->extension = $model->format() ?: $asset->getExtension();
        $this->filename = PretzelHelper::makeFilename($asset, $model);
        $this->url = PretzelHelper::webPath($asset->id) . $this->filename;
        $this->path = PretzelHelper::folderPath($asset->id) . $this->filename;
        $this->quality = $this->transform->quality() ?: 90;
    }


    public function __toString() {
        return $this->getUrl();
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

    public function getPath()
    {
        return $this->path;
    }
}
