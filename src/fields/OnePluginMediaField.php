<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\fields;

use Craft;
use yii\db\Schema;
use craft\base\Field;
use craft\helpers\Json;
use craft\base\ElementInterface;
use craft\web\assets\cp\CpAsset;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\models\OnePluginMediaAsset;

class OnePluginMediaField extends Field
{
    public $mandatory = false;
    public $allowedContents = '*';

    public static function displayName(): string
    {
        return Craft::t('one-plugin-media', 'OnePlugin Media');
    }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function getElementValidationRules(): array
    {
        if( $this->mandatory){
            return [
                ['required']
            ];
        }
        else{
            return [];
        }
    }

    public function rules()
    {
        $rules = parent::rules();
        return $rules;
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        if( $value ==  null)
        {
            return null;
        }
        if ($value instanceof OnePluginMediaAsset)
        {
            return $value;
        }
        
        if (is_array($value) && empty($value))
        {
            return null;
        }
        
        // quick array transform so that we can ensure and `required fields` fire an error
        $valueData = (array)json_decode($value);
        // if we have actual data return model
        if (count($valueData) > 0)
        {
            return new OnePluginMediaAsset($value);
        }
        else{
            return null;
        }
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof OnePluginMediaAsset)
        {
            $value = $value->json;
        }
        return parent::serializeValue($value, $element);
    }

    public function getSettingsHtml()
    {  
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-media/_components/fields/field_settings',
            [
                'field' => $this,
                'availableContents' => $this->availableContent()
            ]
        );
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = OnePluginMedia::$plugin->getSettings();
        
        $folder = 'dist';
        if( OnePluginMedia::$plugin->devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/' . $folder,
            true
        );
        $cssFiles = [];
        $jsFiles = [];

        if( OnePluginMedia::$plugin->devMode ){
            $cssFiles = [$baseAssetsUrl . '/css/onepluginmedia.css',$baseAssetsUrl . '/themes/default/style.css'];
            $jsFiles = [ $baseAssetsUrl . '/js/onepluginmedia.js',$baseAssetsUrl . '/js/spectrum.min.js',$baseAssetsUrl . '/js/jstree.js',$baseAssetsUrl . '/js/selectric.min.js'];
        }
        else{
            $cssFiles = [$baseAssetsUrl . '/css/onepluginmedia.min.css',$baseAssetsUrl . '/themes/default/style.min.css'];
            $jsFiles = [$baseAssetsUrl . '/js/onepluginmedia-cp.min.js'];
        }
                
        foreach ($cssFiles as $cssFile) {
            Craft::$app->getView()->registerCssFile($cssFile);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['depends' => CpAsset::class]);
        }
        
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);
        $jsonVars = [
            'namespace' => $namespacedId,
            'svg-stroke-color' => $settings->svgStrokeColor
        ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new OnePluginMediaInput(" . $jsonVars . ");");

        $allowedContents = is_array($this->allowedContents) ? $this->allowedContents : [$this->allowedContents ];
        $asset = null;
        if( $value != null && ( $value->iconData['type'] == 'imageAsset') ){
            if( isset($value->iconData['id']) && !empty($value->iconData['id'])){
                $asset = Craft::$app->getAssets()->getAssetById($value->iconData['id']);
            }
        }

        return Craft::$app->getView()->renderTemplate(
            'one-plugin-media/_components/fields/field_input',
            [
                'name' => $this->handle,
                'fieldValue' => $value,
                'field' => $this,
                'id' => $id,
                'settings' => $settings,
                'allowedContents' => $allowedContents,
                'asset' => $asset
            ]
        );
    }

    private function availableContent(): array{

        return [['label' => 'All','value' =>'*'], 
                ['label' => 'Images','value' =>'imageAsset'],
                ['label' => 'SVG Icons','value' =>'svg']];
    }
}
