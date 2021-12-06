<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\models;

use Craft;
use craft\base\Model;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\render\BaseRenderer;
use oneplugin\onepluginmedia\render\ImageRenderer;
use oneplugin\onepluginmedia\render\RenderInterface;
use oneplugin\onepluginmedia\render\SVGIconRenderer;

class OnePluginMediaAsset extends Model
{
    // Public Properties
    // =========================================================================

    private $renderers = ["imageAsset" => ["classname" => 'oneplugin\onepluginmedia\render\ImageRenderer', "class" => ImageRenderer::class],
                          "svg"  => ["classname" => 'oneplugin\onepluginmedia\render\SVGIconRenderer', "class" => SVGIconRenderer::class]
                            ]; 
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
        if( $this->iconData && ($this->iconData['type'] == 'imageAsset') )
            return $this->iconData['asset'];
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
        $hash = 'opm_' . md5($this->json . json_encode($options));
        if( $settings->enableCache && Craft::$app->cache->exists($hash)) {
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

    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }
}
