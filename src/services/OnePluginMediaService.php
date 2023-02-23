<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\services;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use craft\base\Component;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\jobs\OptimizeImageJob;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;
use oneplugin\onepluginmedia\records\OnePluginMediaVersion;
use oneplugin\onepluginmedia\records\OnePluginMediaCategory;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageRecord;

class OnePluginMediaService extends Component
{
    const SERVER_URL = 'https://dev.oneplugin.co';

    // Public Methods
    // =========================================================================
    
    public function addRegenerateAllImageOptimizeJob(){

        $queue = Craft::$app->getQueue();
        $assets = OnePluginMediaOptimizedImage::find()->all();
        foreach($assets as $asset){
            Craft::$app->db->createCommand()
            ->upsert(OnePluginMediaOptimizedImage::tableName(), [
                'content' => '',
                'assetId' => $asset->assetId
            ], true, [], true)
            ->execute();

            $jobId = $queue->push(new OptimizeImageJob([
                'description' => Craft::t('one-plugin-media', 'OnePlugin Media - Job for optimizing image with id {id}', ['id' => $asset->assetId]),
                'assetId' => $asset->assetId,
                'force' => true
            ]));
        }
    }

    public function addImageOptimizeJob($assetId, $force,$runQueue = false){

        $assets = OnePluginMediaOptimizedImageRecord::find()->where(['assetId' => $assetId])->all();
        
        if($force){ //Make sure the content is cleared
            Craft::$app->db->createCommand()
                    ->upsert(OnePluginMediaOptimizedImage::tableName(), [
                        'content' => '',
                        'assetId' => $assetId
                    ], true, [], true)
                    ->execute();
        }

        $queue = Craft::$app->getQueue();
        $jobId = $queue->push(new OptimizeImageJob([
            'description' => Craft::t('one-plugin-media', 'OnePlugin Media - Job for optimizing image with id {id}', ['id' => $assetId]),
            'assetId' => $assetId,
            'force' => $force
        ]));

        if($runQueue){
            App::maxPowerCaptain();
            Craft::$app->getQueue()->run();
        }
    }

    public function checkForUpdates( $current_version)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . '/api/update/' . $current_version);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    public function downloadLatestVersion( $json)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . $json['json_path']);
        $response = json_decode($response->getBody(), true);
        $latest_version = '1.0';
        
        foreach ($response as $version => $value) {
            $latest_version = $version;
            $categories = $value['categories'];
            $svgIcons = $value['svg'];

            foreach ($categories as $category) {
                $type = 'svg';
                $parent_id = 0;
                if( !empty($category['parent_id'])){
                    $parent_id = $category['parent_id'];
                }
                $cat = OnePluginMediaCategory::find()->where(['id' => $category['id']] )->all();
                if( count($cat) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginMediaCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ],'id=' . $category['id']);
                    $command->execute();
                }
                else{
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginMediaCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ]);
                    $command->execute();
                }
            }

            foreach ($svgIcons as $svgIcon) {
                $svgs = OnePluginMediaSVGIcon::find()->where(['name' => $svgIcon['fname']] )->all();
                $tags = '';
                if( isset($svgIcon['tags']) )
                    $tags = $svgIcon['tags'];
                if( count($svgs) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginMediaSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ],'name = \'' . $svgIcon['fname'] . '\'');
                    $command->execute();
                }
                else {
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginMediaSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ]);
                    $command->execute();
                }
            }

            Craft::$app->getDb()->createCommand("update onepluginmedia_category set count = (select count(id) from onepluginmedia_svg_icon where onepluginmedia_svg_icon.category = onepluginmedia_category.id) where onepluginmedia_category.type = 'svg'")->execute();
            Craft::$app->plugins->savePluginSettings(OnePluginMedia::$plugin, ['newContentPackAvailable'=>false]);
        }

        $command = Craft::$app->getDb()->createCommand()->update(OnePluginMediaVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();
        return @['success' => true];
    }
    
}
