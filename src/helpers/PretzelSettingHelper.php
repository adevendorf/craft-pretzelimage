<?php
namespace adevendorf\pretzelimage\helpers;

use adevendorf\pretzelimage\Plugin;
use Craft;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;

class PretzelSettingHelper
{
    private static function removeTrailingSlash($string)
    {
        if(substr($string, -1) == '/') {
            $string = substr($string, 0, -1);
        }

        return $string;
    }


    public static function shouldSaveImage()
    {
        return getenv('PRETZEL_SAVE_IMG') && getenv('PRETZEL_SAVE_IMG') == 'false' ? false : true;
    }


    public static function imagePath()
    {
        return getenv('PRETZEL_PATH') ?: "_imgs";
    }


    public static function useCdn()
    {
        return getenv('PRETZEL_CDN') ? true : false;
    }


    public static function cdnPath()
    {
        return self::removeTrailingSlash(getenv('PRETZEL_CDN'));
    }


    public static function webPathHost()
    {

        if (self::useCdn()) {
            return self::cdnPath();
        }

        return self::removeTrailingSlash(Craft::getAlias('@web'));
    }


    public static function isValidHost($hostname): bool
    {
        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            return true;
        }

        if (!$hostname) {
            return false;
        }

        $validHosts = implode(',', [
            getenv('PRETZEL_HOSTS'),
            parse_url(UrlHelper::siteHost(), PHP_URL_HOST)
        ]);

        return strpos($hostname, $validHosts) !== false;
    }

}