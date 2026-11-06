<?php
namespace adevendorf\pretzelimage\helpers;

use Craft;
use adevendorf\pretzelimage\Plugin;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use yii\web\ServerErrorHttpException;

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
        return App::env('PRETZEL_SAVE_IMG') !== 'false';
    }


    /**
     * Local filesystem directory that is served as the site's web root.
     *
     * @webroot is only trusted if it is a real local directory (some sites alias it to a URL).
     *
     * @throws ServerErrorHttpException
     */
    public static function webRoot(): string
    {
        $candidates = [
            App::env('PRETZEL_WEBROOT'),
            Craft::getAlias('@webroot', false),
            Craft::getAlias('@root', false) . '/web',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && !str_contains($candidate, '://') && is_dir($candidate)) {
                return rtrim($candidate, '/');
            }
        }

        throw new ServerErrorHttpException('Pretzel: unable to find the web root; set PRETZEL_WEBROOT');
    }


    public static function imagePath()
    {
        return App::env('PRETZEL_PATH') ?: Plugin::DEFAULT_PATH;
    }


    public static function useCdn()
    {
        return (bool) App::env('PRETZEL_CDN');
    }


    public static function cdnPath()
    {
        return self::removeTrailingSlash((string) App::env('PRETZEL_CDN'));
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
            App::env('PRETZEL_HOSTS'),
            parse_url(UrlHelper::siteHost(), PHP_URL_HOST)
        ]);

        return strpos($validHosts, $hostname) !== false;
    }

}