<?php

namespace adevendorf\pretzelimage\models;

use craft\base\Model;

class Settings extends Model
{
    public $imagePath = '_imgs';
    public $writeToDisk = true;
    public $useCdn = false;
    public $cdnPath = '';

    public function rules()
    {
        return [
            [['imagePath'], 'required'],
        ];
    }
}