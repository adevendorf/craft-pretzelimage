<?php
namespace adevendorf\pretzelimage;

use adevendorf\pretzelimage\helpers\PretzelSettingHelper;
use adevendorf\pretzelimage\services\PretezelService;
use adevendorf\pretzelimage\variables\PretzelVariable;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use craft\base\Plugin as CraftPlugin;
use craft\web\twig\variables\CraftVariable;

class Plugin extends CraftPlugin
{
    public static ?self $plugin = null;

    const DEFAULT_PATH = "_imgs";

    public static function config(): array
    {
        return [
            'components' => [
                'pretzelService' => PretezelService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function(Event $event) {
                $variable = $event->sender;
                $variable->set('pretzel', PretzelVariable::class);
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $path = PretzelSettingHelper::imagePath();

                // SVGs can't be transformed: they are copied unmodified (no "~transforms" segment)
                $event->rules[$path . '/<md5:[a-f0-9]{2}>/<id:\d+>/<filename:[^/]+><ext:\.(?i:svg)>'] = 'pretzelimage/image/copy';
                $event->rules[$path . '/<md5:[a-f0-9]{2}>/<id:\d+>/<filename:[^/]+?>~<transforms:[^/~]+><ext:\.(?i:jpe?g|png|gif|webp|avif)>'] = 'pretzelimage/image/generate';
            }
        );
    }
}
