<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\variables;

use Craft;
use craft\web\View;
use oneplugin\onepluginmedia\OnePluginMedia;

class OnePluginMediaVariable
{
    /**
     * @param bool $includeJQuery
     *
     * @throws \yii\base\InvalidConfigException
     */
    //Kept for legacy calls
    public function includeAssets($jquery = false)
    {
        $settings = OnePluginMedia::$plugin->getSettings();

        $folder = 'dist';
        if( OnePluginMedia::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/' . $folder,
            true
        );

        $cssFiles = [];
        $jsFiles = [];
        if( $jquery ){
            $jsFiles[] = $baseAssetsUrl . '/js/jquery.min.js';
        }

        foreach ($cssFiles as $cssFile) {
            Craft::$app->getView()->registerCssFile($cssFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$cssFile));
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$jsFile) );
        }

        return TemplateHelper::raw(implode(PHP_EOL,[]));
    }
}
