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
use DOMElement;
use DOMDocument;
use craft\helpers\Html;
use oneplugin\onepluginmedia\models\OnePluginMediaAsset;

class BaseRenderer implements RenderInterface
{
    private $defaultSize = ["imageAsset" => ["width" => "100%","height" => "100%"],"svg" => ["width" => "256px","height" => "256px"]];

    public function render(OnePluginMediaAsset $asset, array $options): array{

        return [Html::tag('div', Craft::t('one-plugin-media', 'No renderer found for type ' . $asset->iconData['type'])),false];
    }

    protected function normalizeOptionsForSize(OnePluginMediaAsset $asset,array $options){

        $options['size'] = empty($options['size']) ? false : $options['size'];
        if( $options['size'] ){
            if (empty($options['width'])){
                $options['width'] = $this->defaultSize[$asset->asset['type']]['width'];
            }
            if (empty($options['height'])){
                $options['height'] = $this->defaultSize[$asset->iconData['type']]['height'];
            }    
        }
        return $options;
    }
    protected function setAttribute($doc, $elem, $name, $value){
        
        $attribute = $doc->createAttribute($name);
        $attribute->value = htmlspecialchars($value);
        $elem->appendChild($attribute);
    }

    protected function htmlFromDOMAfterAddingProperties(DOMDocument $doc, DOMElement $element, array $attributes ):string{
        if( $element){
            unset($attributes['width']);
            unset($attributes['height']);
            unset($attributes['class']);
            unset($attributes['size']);
            foreach ($attributes as $key => $value){
                $this->setAttribute($doc,$element,$key,$value);
            }
            $doc->appendChild($element);
        }
        $html = $doc->saveHTML();
        return $html;
    }

    protected function htmlFromDOM(DOMDocument $doc, DOMElement $element):string{
        $doc->appendChild($element);
        $html = $doc->saveHTML();
        return $html;
    }
}