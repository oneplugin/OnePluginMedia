<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\render;

use Craft;
use DOMDocument;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\models\OnePluginMediaAsset;
use oneplugin\onepluginmedia\models\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageModel;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageRecord;
class ImageRenderer extends BaseRenderer
{


    public function render(OnePluginMediaAsset $asset, array $options): array{

        $settings = OnePluginMedia::$plugin->getSettings();
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        $html = '';
        $cache = false;
        try{
            if( $settings->opImageTag == 'img'){
                list($html, $cache) = $this->getImgObject($asset, $attributes);
            }
            else{
                list($html, $cache) = $this->getPictureObject($asset, $attributes);
            }
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
        $srcset = '';
        $optimizedImages = null;
        try{
            $assets = OnePluginMediaOptimizedImageRecord::find()->where(['assetId' => $asset->iconData['id']])->all();
            if( count($assets) > 0 && !empty($assets[0]['content'])){
                $optimizedImages = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
                $srcset = $this->getSrcset($optimizedImages);
                $cache = true; //cache if srcset is available
            }
            else{
                if( isset($asset->iconData['id'])){
                    OnePluginMedia::$plugin->onePluginMediaService->addImageOptimizeJob($asset->iconData['id'], true, false);
                }
            }
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
            if( !empty($srcset)){
                $this->setAttribute($doc,$image,'srcset',$srcset);
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

    private function getPictureObject(OnePluginMediaAsset $asset, $attributes): array
    {
        $cache = false;
        $srcset = '';
        $optimizedImages = null;
        try{
            $assets = OnePluginMediaOptimizedImageRecord::find()->where(['assetId' => $asset->iconData['id']])->all();
            if( count($assets) > 0 && !empty($assets[0]['content'])){
                $optimizedImages = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
                $srcset = $this->getSrcset($optimizedImages);
                $cache = true; //cache if srcset is available
            }
            else{
                if( isset($asset->iconData['id'])){
                    OnePluginMedia::$plugin->onePluginMediaService->addImageOptimizeJob($asset->iconData['id'], true, false);
                }
            }
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $picture = $doc->createElement('picture');
            if( $attributes['size'] ){
                $this->setAttribute($doc,$picture,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }

            if( !empty($srcset)){
                $source = $doc->createElement('source');
                $this->setAttribute($doc,$source,'srcset',$srcset);
                $this->setAttribute($doc,$source,'type','image/'.$optimizedImages->extension);
                $picture->appendChild($source);
            }

            if( $optimizedImages && $optimizedImages->extension == 'webp'){ //Set the fallback urls
                $srcset = $this->getFallbackSrcset($optimizedImages);
                if( !empty($srcset)){
                    $source = $doc->createElement('source');
                    $this->setAttribute($doc,$source,'srcset',$srcset);
                    $this->setAttribute($doc,$source,'type','image/jpeg');
                    $picture->appendChild($source);
                }
            }
            $image = $doc->createElement('img');
            $imageAsset = Craft::$app->getAssets()->getAssetById($asset->iconData['id']);
            if( $imageAsset ){
                $this->setAttribute($doc,$image,'src',$imageAsset->getUrl());
            }
            if( $attributes['size'] ){
                $this->setAttribute($doc,$image,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            empty($attributes['class']) ?:$this->setAttribute($doc,$image,'class',$attributes['class']);
            $picture->appendChild($image);
            empty($attributes['class']) ?:$this->setAttribute($doc,$picture,'class',$attributes['class']);
            empty($asset->iconData['alt']) ? (empty($attributes['alt']) ? (empty($asset->iconData['name']) ?: $this->setAttribute($doc,$picture,'alt',$asset->iconData['name'])) : $this->setAttribute($doc,$picture,'alt',$attributes['alt'])) : $this->setAttribute($doc,$picture,'alt',$asset->iconData['alt']);
            unset($attributes['alt']);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$picture,$attributes),$cache]; ;
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginmedia');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$attributes);
    }

    private function getSrcset(OnePluginMediaOptimizedImageModel $optimizedImage): string
    {
        $srcset = '';
        foreach ($optimizedImage->imageUrls as $key => $value) {
            if( !empty($value['url']) ){
                $srcset .= $value['url'] . ' ' . $key . 'w, ';
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }

    private function getFallbackSrcset(OnePluginMediaOptimizedImageModel $optimizedImage): string
    {
        $srcset = '';
        foreach ($optimizedImage->fallbackImageUrls as $key => $value) {
            if( !empty($value['url']) ){
                $srcset .= $value['url'] . ' ' . $key . 'w, ';
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }
}
