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

class SVGIconRenderer extends BaseRenderer
{
    public function render(OnePluginMediaAsset $asset, array $options): array{
        Craft::$app->getView()->registerCss(
            '.op-svg-animation{animation-duration:1s;animation-fill-mode:both}.op-svg-infinite{animation-iteration-count:infinite}.op-svg-icon-shake{animation-name:op-svg-shake}.op-svg-icon-zoom{animation-name:op-svg-zoomIn}.op-svg-icon-pulse{animation-name:op-svg-pulse}.op-svg-icon-flip{animation-name:op-svg-flipInY}.op-svg-hover{display:inline-block}.op-svg-click{display:inline-block}.op-svg-hover:hover .op-svg-icon-hover-shake,.op-svg-parent-hover:hover .op-svg-icon-hover-shake{animation-name:op-svg-shake}.op-svg-hover:hover .op-svg-icon-hover-zoom,.op-svg-parent-hover:hover .op-svg-icon-hover-zoom{animation-name:op-svg-zoomIn}.op-svg-hover:hover .op-svg-icon-hover-pulse,.op-svg-parent-hover:hover .op-svg-icon-hover-pulse{animation-name:op-svg-pulse}.op-svg-hover:hover .op-svg-icon-hover-flip,.op-svg-parent-hover:hover .op-svg-icon-hover-flip{animation-name:op-svg-flipInY}.op-svg-hover:hover .op-svg-icon-hover-flipX,.op-svg-parent-hover:hover .op-svg-icon-hover-flipX{animation-name:op-svg-flipInX}.op-svg-click:active .op-svg-icon-click-shake,.op-svg-parent-click:active .op-svg-icon-click-shake{animation-name:op-svg-shake}@keyframes op-svg-flipInY{from{transform:perspective(400px) rotate3d(0,1,0,90deg);animation-timing-function:ease-in;opacity:0}40%{transform:perspective(400px) rotate3d(0,1,0,-20deg);animation-timing-function:ease-in}60%{transform:perspective(400px) rotate3d(0,1,0,10deg);opacity:1}80%{transform:perspective(400px) rotate3d(0,1,0,-5deg)}to{transform:perspective(400px)}}@keyframes op-svg-flipInX{from{transform:perspective(400px) rotate3d(1,0,0,90deg);animation-timing-function:ease-in;opacity:0}40%{transform:perspective(400px) rotate3d(1,0,0,-20deg);animation-timing-function:ease-in}60%{transform:perspective(400px) rotate3d(1,0,0,10deg);opacity:1}80%{transform:perspective(400px) rotate3d(1,0,0,-5deg)}to{transform:perspective(400px)}}@keyframes op-svg-shake{from,to{transform:translate3d(0,0,0)}10%,30%,50%,70%,90%{transform:translate3d(-3px,0,0)}20%,40%,60%,80%{transform:translate3d(3px,0,0)}}@keyframes op-svg-pulse{from{transform:scale3d(1,1,1)}50%{transform:scale3d(1.2,1.2,1.2)}to{transform:scale3d(1,1,1)}}@keyframes op-svg-zoomIn{from{opacity:1;transform:scale3d(.5,.5,.5)}50%{opacity:1}}'
        );
        Craft::$app->getView()->registerJs(
            '$(".op-svg-animation").parent().addClass("op-svg-hover");'
        );
        $plugin = OnePluginMedia::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        try{
            $svg = $doc->createElement('svg');
            $animationClass = '';
            if( $asset->iconData['asset']['icon-animation'] != null && !empty($asset->iconData['asset']['icon-animation']) ){
                $animationClass = 'op-svg-animation op-svg-icon-hover-' . $asset->iconData['asset']['icon-animation'];
            }
            empty($attributes['class']) ? $this->setAttribute($doc,$svg,'class',$animationClass):$this->setAttribute($doc,$svg,'class',$attributes['class'] . ' ' . $animationClass);
            if( $attributes['size'] ){
                $this->setAttribute($doc,$svg,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            $this->setAttribute($doc,$svg,'stroke-width',$asset->iconData['asset']['icon-stroke-width']);
            $this->setAttribute($doc,$svg,'stroke',$asset->iconData['asset']['icon-primary']);
            $this->setAttribute($doc,$svg,'viewbox','0 0 24 24');
            $this->setAttribute($doc,$svg,'fill','none');
            $this->setAttribute($doc,$svg,'stroke-linecap','round');
            $this->setAttribute($doc,$svg,'stroke-linejoin','round');
            $svg->appendChild($doc->createCDATASection($asset->iconData['asset']['svg-data']));
            return [$this->htmlFromDOMAfterAddingProperties($doc,$svg,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginmedia');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
}