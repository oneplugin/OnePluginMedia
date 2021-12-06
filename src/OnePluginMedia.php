<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia;

use Craft;
use yii\base\Event;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Fields;
use craft\services\Plugins;
use craft\helpers\UrlHelper;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use oneplugin\onepluginmedia\models\Settings;
use oneplugin\onepluginmedia\fields\OnePluginMediaField;
use oneplugin\onepluginmedia\services\OnePluginMediaService;


class OnePluginMedia extends Plugin
{
    public static $plugin;
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = true;
    public $devMode = false;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'onePluginMediaService' => OnePluginMediaService::class,
        ]);

        $this->initRoutes();
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = OnePluginMediaField::class;
            }
        );
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    if ($event->plugin === $this) {
                        if ('one-plugin-media' !== $event->plugin->handle) {
                            return;
                        }
                        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                            return;
                        }
                        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('one-plugin-media/welcome'))->send();
                    }
                }
            }
        );
        Craft::info(
            Craft::t(
                'one-plugin-media',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getPluginName()
    {
        $settings = $this->getSettings();
        return Craft::t('one-plugin-media', $this->getSettings()->pluginName);
    }

    public function getCpNavItem()
    {
        $settings = $this->getSettings();
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getPluginName();
        $navItem['subnav']['settings'] = ['label' => Craft::t('one-plugin-media', 'Settings'), 'url' => 'one-plugin-media/settings'];
        $navItem['subnav']['content-sync'] = ['label' => Craft::t('one-plugin-media', 'Content Sync'), 'url' => 'one-plugin-media/settings/sync'];
        return $navItem;
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    private function initRoutes()
    {

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {

            $event->rules['one-plugin-media/'] = 'one-plugin-media/one-plugin-media/index';
            $event->rules['one-plugin-media/default'] = 'one-plugin-media/one-plugin-media/index';

            $event->rules['one-plugin-media/settings'] = 'one-plugin-media/settings/index';
            $event->rules['one-plugin-media/settings/sync'] = 'one-plugin-media/settings/sync';
            $event->rules['one-plugin-media/settings/save-settings'] = 'one-plugin-media/settings/save-settings';
            $event->rules['one-plugin-media/settings/check-for-updates'] = 'one-plugin-media/settings/check-for-updates';
            $event->rules['one-plugin-media/settings/download-files'] = 'one-plugin-media/settings/download-files';
        });
    }
}
