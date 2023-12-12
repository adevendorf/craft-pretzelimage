<?php
namespace adevendorf\pretzelimage\variables;

use adevendorf\pretzelimage\Plugin;
use craft\elements\Asset;

class PretzelVariable
{
    public function url(Asset $asset, $transforms = [], $defaults = [])
    {
        return Plugin::$plugin->pretzelService->url($asset, $transforms, $defaults);
    }
}