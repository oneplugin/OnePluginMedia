<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\jobs;

use Craft;
use Throwable;
use craft\helpers\Image;
use craft\queue\BaseJob;
use craft\elements\Asset;
use oneplugin\onepluginmedia\OnePluginMedia;
use craft\imagetransforms\ImageTransformer;
use craft\models\ImageTransform as AssetTransform;
use oneplugin\onepluginmedia\models\OnePluginMediaOptimizedImage;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageRecord;

class OptimizeImageJob extends BaseJob
{
    /**
     * @var Asset - The asset Id of the image
     */
    public $assetId;

    /**
     * @var force - force asset generation
     */
    public $force;

    public function getDescription(): string
    {
        return Craft::t('one-plugin-media', 'Generating Optimized images.');
    }

    public function execute($queue): void
    {
        Craft::$app->getElements()->invalidateCachesForElementType(Asset::class);

        $asset = Craft::$app->getAssets()->getAssetById($this->assetId);
        if( $asset ){
            $assets = OnePluginMediaOptimizedImageRecord::find()->where(['assetId' => $this->assetId] )->all();
            if( count($assets) > 0 && !$this->force){
                return;
            }

            $model = $this->generateOptimizedImage($asset, $this->force);
            $json = json_encode($model);
            Craft::$app->db->createCommand()
                    ->upsert(OnePluginMediaOptimizedImageRecord::tableName(), [
                        'content' => $json,
                        'assetId' => $asset->getId()
                    ], true, [], true)
                    ->execute();
            
        }
        else{
            OnePluginMediaOptimizedImageRecord::find()->where(['assetId' => $this->assetId])->one()->delete();
        }
    }

    private function generateOptimizedImage(Asset $asset, $force){

        $settings = OnePluginMedia::$plugin->getSettings();
        $imageAspect = $asset->width / $asset->height;
        $model = new OnePluginMediaOptimizedImage('');

        $inputFormat = $asset->extension;
        if( strtolower($inputFormat) == 'svg'){
            //SVG's are not supported yet
            $model->originalUrl = $asset->getUrl();
            $model->width = $asset->width;
            $model->height = $asset->height;
            $model->name = $asset->title;
            $model->extension = 'svg';
            return $model;
        }

        $generateTransformsBeforePageLoad = Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = true;
        
        $outputFormat = '';
        if( $settings->opOutputFormat == 'same'){
            $outputFormat = $inputFormat;
        }
        else{
            $outputFormat = $settings->opOutputFormat;
        }
        if (Image::canManipulateAsImage($outputFormat) && Image::canManipulateAsImage($inputFormat) && $asset->width > 0 && $asset->height > 0 ){
            foreach( $settings->opImageVariants as $size ){
                $opWidth = (int)$size['opWidth'];
                $opHeight = (int)((int)$size['opWidth'] / $imageAspect);
                try{
                    try {
                        $transform = new AssetTransform();
                        $transform->format = $outputFormat;
                        $transform->quality = $size['opQuality'];
                        $transform->width = $opWidth;
                        $transform->height = $opHeight;
                        $transform->interlace = 'line'; //for progressive jpgs

                        $transforms = Craft::createObject(ImageTransformer::class);
                        $index = $transforms->getTransformIndex($asset, $transform);
                        $index->fileExists = 0;
                        $transforms->storeTransformIndexData($index);
                        try {
                            $transforms->deleteImageTransformFile($asset, $index);
                        } catch (Throwable $exception) {
                        }
                        //No need to delete the file as this is done by the ImgeTransformer
                    } 
                    catch (\Throwable $e) {
                        $message = 'Failed to delete transform: '.$e->getMessage();
                        Craft::error($message, __METHOD__);
                        $model->errors[] = $message;
                    }

                    if( !$settings->opUpscale && ($asset->width < $opWidth || $asset->height < $opHeight )){
                        $model->imageUrls[$opWidth] = ['url' => '','width'=>$transform->width,'height'=>$transform->height,'size'=>'0'];
                        continue;
                    }

                    $transform = new AssetTransform();
                    $transform->format = $outputFormat;
                    $transform->quality = $size['opQuality'];
                    $transform->width = $opWidth;
                    $transform->height = $opHeight;
                    $transform->interlace = 'line'; //for progressive jpgs

                    list($image,$errors) = $this->generateImageVariant($asset, $transform);
                    $model->imageUrls[$opWidth] = $image;
                    if( !empty($errors) ){
                        $model->errors[] = $errors;
                    }

                    if( $outputFormat == 'webp'){ //Old version of Safari browser doesn't support webp. We need a fallback in that case.
                        $transform->format = 'jpg';
                        list($image,$errors) = $this->generateImageVariant($asset, $transform);
                        $model->fallbackImageUrls[$opWidth] = $image;
                        if( !empty($errors) ){
                            $model->errors[] = $errors;
                        }
                    }
                }
                catch(\Throwable $e) {
                    $message = 'Failed to create transform: '.$e->getMessage();
                    Craft::error($message, __METHOD__);
                    $model->errors[] = $message;
                }
            }
            $model->originalUrl = $asset->getUrl();
            $model->width = $asset->width;
            $model->height = $asset->height;
            $model->name = $asset->title;
            $model->extension = $outputFormat;
        }
        else{
            Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;
            return null;
        }
        Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad = $generateTransformsBeforePageLoad;
        return $model;
    }

    private function generateImageVariant($asset,$transform): array{

        $image = '';
        $filesize = 0;
        $errors = '';
        try{
            $url = $asset->getUrl($transform);
            
            if( $url ){
                
                /*if( ini_get('allow_url_fopen') ) {
                    $headers = get_headers($url, true);
                    if( isset($headers['Content-Length']) ){
                        $filesize = $headers['Content-Length'];
                    }
                }*/
                $image = ['url' => $url,'width'=>$transform->width,'height'=>$transform->height,'filesize'=>$filesize];
            }
        }
        catch(\Throwable $e) {
            $errors = 'Failed to create transform: '.$e->getMessage();
            Craft::error($errors, __METHOD__);
        }
        finally{
            return [$image,$errors];
        }
        
    }
    private function printTransform($transform){

        return '_' . ($transform->width ?: 'AUTO') . 'x' . ($transform->height ?: 'AUTO') .
            '_' . $transform->mode .
            '_' . $transform->position .
            ($transform->quality ? '_' . $transform->quality : '') .
            '_' . $transform->interlace;
    }
    private function generatePlaceholderImage(Asset $asset){



    }

}
