<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\services;

use Craft;
use GuzzleHttp\Client;
use craft\base\Component;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;
use oneplugin\onepluginmedia\records\OnePluginMediaVersion;
use oneplugin\onepluginmedia\records\OnePluginMediaCategory;


class OnePluginMediaService extends Component
{
    const SERVER_URL = 'https://dev.oneplugin.co';

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
                if($category['type'] == 'ANIMATEDICON'){
                    continue;
                }
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
        }

        $command = Craft::$app->getDb()->createCommand()->update(OnePluginMediaVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();
        return @['success' => true];
    }
    
}
