<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\render;

use Craft;
use DOMDocument;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\models\OnePluginMediaAsset;

class ImageRenderer extends BaseRenderer
{

    public function render(OnePluginMediaAsset $asset, array $options): array{

        $settings = OnePluginMedia::$plugin->getSettings();
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        $html = '';
        $cache = false;
        try{
            list($html, $cache) = $this->getImgObject($asset, $attributes);
            return[$html, $cache];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginmedia');
        }
        
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
    
    private function getImgObject(OnePluginMediaAsset $asset, $attributes): array
    {
        $cache = false;
        try{
            
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $image = $doc->createElement('img');
            $imageAsset = Craft::$app->getAssets()->getAssetById($asset->iconData['id']);
            if( $imageAsset ){
                $this->setAttribute($doc,$image,'src',$imageAsset->getUrl());
            }
            if( $attributes['size'] ){
                $this->setAttribute($doc,$image,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            
            empty($attributes['class']) ?:$this->setAttribute($doc,$image,'class',$attributes['class']);
            empty($asset->iconData['alt']) ? (empty($attributes['alt']) ? (empty($asset->iconData['name']) ?: $this->setAttribute($doc,$image,'alt',$asset->iconData['name'])) : $this->setAttribute($doc,$image,'alt',$attributes['alt'])) : $this->setAttribute($doc,$image,'alt',$asset->iconData['alt']);
            unset($attributes['alt']);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$image,$attributes),$cache]; ;
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginmedia');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$attributes);
    }
}