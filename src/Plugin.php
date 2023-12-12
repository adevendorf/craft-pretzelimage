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
    public static $plugin;

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->setComponents([
            'pretzelService' => PretezelService::class
        ]);

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
                $event->rules[PretzelSettingHelper::imagePath() . '/<md5:.+>/<id:\d+>/<filename:.+>~<transforms:.+><ext:(\.JPG|\.jpg|\.PNG|\.png|\.JPEG|\.jpeg)>'] = 'pretzelimage/image/generate';
            }
        );
    }
}
