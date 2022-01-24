<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\controllers;

use Craft;
use yii\web\Response;
use craft\web\Controller;
use craft\web\assets\cp\CpAsset;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\helpers\StringHelper;
use oneplugin\onepluginmedia\records\OnePluginMediaVersion;

class SettingsController extends Controller
{

    public $plugin;

    public function init()
    {
        $this->requireAdmin();
        $this->plugin = OnePluginMedia::$plugin;
        parent::init();
    }

    public function actionIndex(): Response
    {
        $settings = $this->plugin->getSettings();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/dist',
            true
        );
        
        Craft::$app->getView()->registerCssFile($baseAssetsUrl . '/css/onepluginmedia.min.css');
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/spectrum.min.js',['depends' => CpAsset::class]);

        return $this->renderTemplate('one-plugin-media/settings/_general', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSync(): Response
    {
        $settings = $this->plugin->getSettings();
        $version = OnePluginMediaVersion::latest_version();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/dist',
            true
        );
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/party.min.js',['depends' => CpAsset::class]);
        return $this->renderTemplate('one-plugin-media/settings/_sync', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings,
                    'version' => $version,
                    'formatted_version' => number_format((float)$version, 1, '.', '')
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $postData = Craft::$app->request->post('settings', []);
        $this->plugin->setSettings($postData);
        
        if (Craft::$app->plugins->savePluginSettings($this->plugin, $postData)) {
            Craft::$app->session->setNotice(Craft::t('one-plugin-media','Settings Saved'));
            return $this->redirectToPostedUrl();
        }
        $errors = $this->plugin->getSettings()->getErrors();
        Craft::$app->session->setError(
            implode("\n", StringHelper::flattenArrayValues($errors))
        );
    }

    public function actionCheckForUpdates()
    {
        $version = OnePluginMediaVersion::latest_version();
        $response = $this->plugin->onePluginMediaService->checkForUpdates($version);
        return $this->asJson($response);
    }

    public function actionDownloadFiles(){

        $version = OnePluginMediaVersion::latest_version();
        $response = $this->plugin->onePluginMediaService->checkForUpdates($version);
        return $this->asJson($this->plugin->onePluginMediaService->downloadLatestVersion($response));

    }

    private function implode_all($glue, $arr){            
        for ($i=0; $i<count($arr); $i++) {
            if (@is_array($arr[$i])) 
                $arr[$i] = $this->implode_all ($glue, $arr[$i]);
        }            
        return implode($glue, $arr);
    }
}