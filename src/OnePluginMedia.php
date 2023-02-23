<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia;

use Craft;
use yii\base\Event;
use craft\base\Model;
use yii\web\Response;

use craft\base\Plugin;
use craft\elements\Asset;
use craft\web\UrlManager;
use craft\services\Assets;
use craft\services\Fields;
use craft\services\Plugins;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\events\PluginEvent;
use craft\events\ElementEvent;
use craft\events\ReplaceAssetEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\TemplateResponseBehavior;
use craft\web\twig\variables\CraftVariable;
use oneplugin\onepluginmedia\models\Settings;
use craft\events\RegisterComponentTypesEvent;
use oneplugin\onepluginmedia\variables\OnePluginMediaVariable;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage;
use oneplugin\onepluginmedia\fields\OnePluginMedia as OnePluginMediaField;
use oneplugin\onepluginmedia\services\OnePluginMediaService as OnePluginMediaService;

class OnePluginMedia extends Plugin
{

    const TRANSLATION_CATEGORY = 'one-plugin-media';
    
    public static $plugin;

    public static $devMode = false;

    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'onePluginMediaService' => OnePluginMediaService::class,
        ]);

        $this->initRoutes();

        Event::on(Fields::class,Fields::EVENT_REGISTER_FIELD_TYPES,function (RegisterComponentTypesEvent $event) {
                $event->types[] = OnePluginMediaField::class;
            }
        );

        Event::on(CraftVariable::class,CraftVariable::EVENT_INIT,function (Event $event) {
                $variable = $event->sender;
                $variable->set('onePluginMedia', OnePluginMediaVariable::class);
            }
        );

        Event::on(Assets::class,Assets::EVENT_AFTER_REPLACE_ASSET,function (ReplaceAssetEvent $event) {
                $asset = $event->asset;
                $assets = OnePluginMediaOptimizedImage::find()->where(['assetId' => $asset->id] )->all();
                if( count($assets) == 0 ){
                    return;
                }
                $this->onePluginMediaService->addImageOptimizeJob($asset->id, true, true);
            }
        );

        Event::on(Elements::class,Elements::EVENT_AFTER_DELETE_ELEMENT,function (ElementEvent $event) {
                if( $event->element instanceof Asset ){
                    $asset = $event->element;
                    $assets = OnePluginMediaOptimizedImage::find()->where(['assetId' => $asset->id] )->all();
                    if( count($assets) == 0 ){
                        return;
                    }
                    OnePluginMediaOptimizedImage::find()->where(['assetId' => $asset->id])->one()->delete();
                }
            }
        );

        Event::on(Plugins::class,Plugins::EVENT_AFTER_INSTALL_PLUGIN,function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // If installed plugin isn't OnePlugin Media, bail out
                    if ('one-plugin-media' !== $event->plugin->handle) {
                        return;
                    }
                    // If installed via console, no need for a redirect
                    if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                        return;
                    }
                    // Redirect to the plugin's settings page (with a welcome message)
                    Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('one-plugin-media/welcome'))->send();
                }
            }
        );
    }

    public function getPluginName()
    {
        $settings = $this->getSettings();
        return Craft::t('one-plugin-media', $this->getSettings()->pluginName);
    }

    public function getCpNavItem():array
    {
        $settings = $this->getSettings();
        $navItem = parent::getCpNavItem();
        $navItem['label'] = $this->getPluginName();
        if( $settings->newContentPackAvailable ){
            $navItem['badgeCount'] = 1;
        }
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $navItem['subnav']['settings'] = ['label' => Craft::t('one-plugin-media', 'Settings'), 'url' => 'one-plugin-media/settings'];
            $navItem['subnav']['svg-icon-packs'] = ['label' => Craft::t('one-plugin-media', 'SVG Icon Packs'), 'url' => 'one-plugin-media/svg-icons'];
            if( $settings->newContentPackAvailable ){
                $navItem['subnav']['content-sync'] = ['label' => Craft::t('one-plugin-media', 'Content Sync'), 'url' => 'one-plugin-media/settings/sync','badgeCount' => 1];
            }
            else{
                $navItem['subnav']['content-sync'] = ['label' => Craft::t('one-plugin-media', 'Content Sync'), 'url' => 'one-plugin-media/settings/sync'];
            }
        }
        return $navItem;
    }

    public static function t(string $message, array $params = [], string $language = null): string
    {
        return \Craft::t(self::TRANSLATION_CATEGORY, $message, $params, $language);
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    public function getSettingsResponse(): TemplateResponseBehavior|Response
    {
        $url = UrlHelper::cpUrl('one-plugin-media/settings');
        return Craft::$app->getResponse()->redirect($url);
    }

    private function initRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {

            $event->rules['one-plugin-media/'] = 'one-plugin-media/one-plugin/index';
            $event->rules['one-plugin-media/default'] = 'one-plugin-media/one-plugin/index';

            $event->rules['one-plugin-media/settings'] = 'one-plugin-media/settings/index';
            $event->rules['one-plugin-media/settings/sync'] = 'one-plugin-media/settings/sync';
            $event->rules['one-plugin-media/settings/save-settings'] = 'one-plugin-media/settings/save-settings';
            $event->rules['one-plugin-media/settings/check-for-updates'] = 'one-plugin-media/settings/check-for-updates';
            $event->rules['one-plugin-media/settings/download-files'] = 'one-plugin-media/settings/download-files';
            
            $event->rules['one-plugin-media/svg-icons'] = 'one-plugin-media/svg-icons/index';
            $event->rules['one-plugin-media/svg-icons/new'] = 'one-plugin-media/svg-icons/new';
            $event->rules['one-plugin-media/svg-icons/save'] = 'one-plugin-media/svg-icons/save';
            $event->rules['one-plugin-media/svg-icons/edit/<iconPackId:\d+>'] = 'one-plugin-media/svg-icons/edit';
            
            $event->rules['one-plugin-media/one-plugin/load'] = 'one-plugin-media/one-plugin/load';
            $event->rules['one-plugin-media/one-plugin/show'] = 'one-plugin-media/one-plugin/show';
            $event->rules['one-plugin-media/one-plugin/create-optimized-image'] = 'one-plugin-media/one-plugin/create-optimized-image';
            $event->rules['one-plugin-media/one-plugin/icons-by-category/<id:\d+>'] = 'one-plugin-media/one-plugin/icons-by-category';
            $event->rules['one-plugin-media/one-plugin/search-icons-svg/<text:\d+>'] = 'one-plugin-media/one-plugin/search-icons-svg';
            $event->rules['one-plugin-media/one-plugin/search-icons-aicon/<text:\d+>'] = 'one-plugin-media/one-plugin/search-icons-aicon';
            $event->rules['one-plugin-media/one-plugin/check-asset/<assetId:\d+>'] = 'one-plugin-media/one-plugin/check-asset';

        });
    }

}
