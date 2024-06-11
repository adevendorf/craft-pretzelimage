<?php

namespace adevendorf\pretzelimage\models;

use adevendorf\pretzelimage\helpers\TransformationHelper;
use craft\elements\Asset;

class TransformModel
{
    private $t;
    private $asset;

    public function __construct(Asset $asset, $transforms)
    {
        $this->asset = $asset;

        if (gettype($transforms) === 'string') {
            $this->t = $this->createFromString($transforms);
        }

        if (gettype($transforms) === 'array') {
            $this->t = $this->createFromArray($transforms);
        }
    }

    private function createFromString(string $string): array
    {
        return TransformationHelper::convertStringToArray($string);
    }

    private function createFromArray(array $array): array
    {
        return TransformationHelper::cleanTransformationArray($array);
    }

    public function getTransforms()
    {
        return $this->t;
    }

    public function width()
    {
        return isset($this->t['width']) ? $this->t['width'] : false;
    }

    public function height()
    {
        return isset($this->t['height']) ? $this->t['height'] : false;
    }

    public function ratio()
    {
        return isset($this->t['ratio']) ? $this->t['ratio'] : false;
    }

    public function quality()
    {
        return isset($this->t['quality']) ? $this->clampValue($this->t['quality'], 25, 100) : 65;
    }

    public function background()
    {
        return isset($this->t['background']) ? $this->t['background'] : false;
    }

    public function mode()
    {
        return isset($this->t['mode']) ? $this->t['mode'] : false;
    }

    public function position()
    {
        return isset($this->t['position']) ? $this->t['position'] : false;
    }

    public function format()
    {
        return isset($this->t['format']) ? $this->t['format'] : false;
    }


    public function toTransformString(): string
    {
        return TransformationHelper::convertArrayToString($this->t);
    }

    private function clampValue($value, $min, $max){
        return max($min, min($max, $value));
    }
}