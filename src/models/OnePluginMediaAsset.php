<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\models;

use Craft;
use oneplugin\onepluginmedia\OnePluginMedia;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginmedia\gql\models\ImageGql;
use oneplugin\onepluginmedia\render\BaseRenderer;
use oneplugin\onepluginmedia\render\ImageRenderer;
use oneplugin\onepluginmedia\gql\models\SVGIconGql;
use oneplugin\onepluginmedia\render\RenderInterface;
use oneplugin\onepluginmedia\render\SVGIconRenderer;

class OnePluginMediaAsset
{
    private $defaultSize = ["svg" => ["width" => "256px","height" => "256px"],"imageAsset" => ["width" => "100%","height" => "100%"]];
    private $renderers = ["imageAsset" => ["classname" => 'oneplugin\onepluginmedia\render\ImageRenderer', "class" => ImageRenderer::class],
                          "svg"  => ["classname" => 'oneplugin\onepluginmedia\render\SVGIconRenderer', "class" => SVGIconRenderer::class]]; 
	public $output = '';
    public $json = '';
    public $iconData = null;

    public function __construct($value)
    {
        if($this->validateJson($value)){
        	$this->json = $value;
        	$this->iconData = (array)json_decode($value,true);
        } else {
            $value = null;
            $this->iconData = null;
        }
    }

    public function __toString()
    {
        return $this->output;
    }

    public function url()
    {
        if( $this->iconData && ($this->iconData['type'] == 'imageAsset') ) {
            $asset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
            if( $asset ){
                return $asset->getUrl();
            }
        }
        return "";
    }

    public function type()
    {
        if( $this->iconData )
            return $this->iconData['type'];
        return "";
    }

    public function render(array $options = [])
    {
        $settings = OnePluginMedia::$plugin->getSettings();
        $hash = 'op_' . $settings->opSettingsHash . '_' . $settings->opImageTag . '_' . $settings->aIconDataAsHtml . md5($this->json . json_encode($options));
        if( $settings->enableCache && Craft::$app->cache->exists($hash)) {
            $renderer = $this->createAssetRenderer();
            $renderer->includeAssets();
            return TemplateHelper::raw(\Craft::$app->cache->get($hash));
        }
        $cache = true;
        $renderer = $this->createAssetRenderer();
        if( $renderer != null){
            list($html,$cache) = $renderer->render($this,$options);
            if( $settings->enableCache && $cache ){
                Craft::$app->cache->set($hash, $html,86400);
            }
            return TemplateHelper::raw($html);;
        }
        return TemplateHelper::raw('<div>Renderer Exception </div>');
    }

    public function getThumbHtml(){
        
        if( $this->iconData )
        {
            if( ($this->iconData['type'] == 'imageAsset') && isset($this->iconData['id']) && $this->iconData['id'] != null){
                $asset = Craft::$app->getAssets()->getAssetById((int) $this->iconData['id']);        
                if( $asset )
                {
                    return TemplateHelper::raw( $asset->getPreviewThumbImg(34,34) );
                }
            }
        }
        return '';
    }

    public function getName() {
        return 'OnePluginMedia';
    }

    public function getType() {
        return $this->iconData['type'];
    }

    public function getJsAssets() {

        return [];
    }
    
    public function getTag($args) {
        $opts = $args['options'] ?? [];
        $options = array();
        foreach ($opts as $opt) {
            foreach ($opt as $key => $value) {
                $options[$key] = $value;
            }
        }
        return $this->render($options);
    }

    public function getImage() {
        if( $this->iconData['type'] == 'imageAsset'){
            return new ImageGql($this->json);
        }
        return null;
    }

    public function getSvgIcon() {
        if( $this->iconData['type'] == 'svg'){
            return new SVGIconGql($this->json);
        }
        return null;
    }

    public function getSrc()
    {
        switch( (string)$this->iconData['type'] ){
            case 'imageAsset':
                $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
                if( $imageAsset ){
                    return $imageAsset->getUrl();
                }
                break;
            case 'svg':
                return '';
            }
        
    }

    private function createAssetRenderer(): RenderInterface
    {
        /** @var RenderInterface $renderer */
        $renderer = null;
        try {
            if( isset( $this->renderers[$this->iconData['type']] ) ){
                $renderer = Craft::createObject($this->renderers[$this->iconData['type']]["classname"]);
                if( $renderer instanceof $this->renderers[$this->iconData['type']]["class"]) {
                    return $renderer;
                }
            }
            $renderer = new BaseRenderer();
        } catch (\Throwable $e) {
            $renderer = new BaseRenderer();
            Craft::error($e->getMessage(), __METHOD__);
        }
        return $renderer;
    }

    private function normalizeOptions(array $options){

        if (empty($options['width'])){
            $options['width'] = $this->defaultSize[$this->iconData['type']]['width'];
        }
        if (empty($options['height'])){
            $options['height'] = $this->defaultSize[$this->iconData['type']]['height'];
        }

        return $options;
    }
    
    private function setAttribute($doc, $elem, $name, $value){
        
        $attribute = $doc->createAttribute($name);
        $attribute->value = htmlspecialchars($value);
        $elem->appendChild($attribute);
    }
    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }
}
