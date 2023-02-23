<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */


namespace oneplugin\onepluginmedia\controllers;

use Craft;
use yii\web\Response;
use craft\db\Paginator;
use craft\web\Controller;

use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;
use oneplugin\onepluginmedia\records\OnePluginMediaCategory;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage;
use oneplugin\onepluginmedia\models\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageModel;

class OnePluginController extends Controller
{

    public $plugin;
    protected array|bool|int $allowAnonymous = true;
    const QUERY_PAGE_SIZE = 30;

    public function init():void
    {
        $this->plugin = OnePluginMedia::$plugin;
        parent::init();
    }
    public function actionIndex()
    {

        $url = "one-plugin-media/settings";
        return $this->redirect($url);

    }

    public function actionShow(): Response
    {
        $this->requirePostRequest();
        $settings = $this->plugin->getSettings();
        return $this->renderTemplate('one-plugin-media/icon_selector/index', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings,
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionOptimizeDialog(): Response
    {
        $this->requirePostRequest();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $userSession = Craft::$app->getUser();
        $asset = Craft::$app->getAssets()->getAssetById($assetId);
        $settings = $this->plugin->getSettings();
        if( $asset ){
            $assets = OnePluginMediaOptimizedImage::find()->where(['assetId' => $assetId] )->all();
            $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($asset) !== null;
            if( count($assets) > 0 ){
                if( !empty($assets[0]['content'] ) ){
                    $optimizedImage = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
                    return $this->renderTemplate('one-plugin-media/image_optimize/index', array_merge(
                        [
                            'plugin' => $this->plugin,
                            'settings' => $settings,
                            'derivations' => $optimizedImage,
                            'asset' => $asset,
                            'previewable' => $previewable
                        ],
                        Craft::$app->getUrlManager()->getRouteParams())
                    );
                }
            }
            else{
                $this->plugin->onePluginMediaService->addImageOptimizeJob($assetId, true, false);
            }
        }
        return $this->renderTemplate('one-plugin-media/image_optimize/processing', array_merge(
            [
                'plugin' => $this->plugin,
                'settings' => $settings,
                'asset' => $asset
            ],
            Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSvgCategories()
    {
        $categories = OnePluginMediaCategory::find()
            ->orderBy(['name' => SORT_ASC])
            ->where(['type' => 'svg'])
            ->all();
        $json = array();
        $json[] = array("id" => "0","text" => 'SVG Icons','parent'=>'#');
        $json[] = array("id" => "latest","text" => "Latest Release","parent" => 0);
        foreach($categories as $category){
            $parent = '0';
            if(!empty($category->parent_id)){
                $parent = $category->parent_id;
            }
            $json[] = array("id" => strval($category->id),"text" => $category->name . '(' . $category->count . ')',"parent" => $parent);
        }
        return json_encode($json);
    }

    public function actionIconsByCategory($id = null, $type = 'aicon',$filter = 'all',$pageNum = 0)
    {
        if( $type == 'svg'){
            $query = null;
            if( $id == 'latest'){
                $query = OnePluginMediaSVGIcon::find()
                ->limit(100)
                ->orderBy(['dateUpdated' => SORT_DESC]);
            }
            else{
                $query = OnePluginMediaSVGIcon::find()
                ->where(['category' => $id])
                ->orderBy(['id' => SORT_ASC]);
            }
            

            $pages = new Paginator($query,[
                'pageSize' => self::QUERY_PAGE_SIZE,
                'currentPage' => $pageNum,
            ]);
            $pageResults = $pages->getPageResults();
            $result = [];
            $result['data'] = $pageResults;
            $result['total'] = $pages->totalResults;
            $result['pages'] = $pages->totalPages;
            $result['currentPage'] = $pages->currentPage;
            return $this->asJson($result);
        }
        return $this->asJson(['success' => true, 'data' => []]);
    }

    public function actionSearchIconsSvg($text = null,$pageNum)
    {
        $query = OnePluginMediaSVGIcon::find()
            ->where(['like','tags','%' . $text . '%', false])
            ->orderBy(['title' => SORT_ASC]);
        $pages = new Paginator($query,[
            'pageSize' => self::QUERY_PAGE_SIZE,
            'currentPage' => $pageNum,
        ]);
        $pageResults = $pages->getPageResults();
        $result = [];
        $result['data'] = $pageResults;
        $result['total'] = $pages->totalResults;
        $result['pages'] = $pages->totalPages;
        $result['currentPage'] = $pages->currentPage;
        return  $this->asJson($result);
    }

    public function actionCreateOptimizedImage(){

        $this->requirePostRequest();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $force = Craft::$app->getRequest()->getBodyParam('force');

        $this->plugin->onePluginMediaService->addImageOptimizeJob($assetId, $force, false);
        return $this->asJson(['success' => true]);
    }

    public function actionCheckAsset($assetId){

        $assets = OnePluginMediaOptimizedImage::find()->where(['assetId'=>$assetId])->all();
        if( count($assets) > 0 ){
            if( !empty($assets[0]['content']) ){
                return $this->asJson(['result' => true]);
            }
        }
        
        return $this->asJson(['result' => false]);
    }
    
}
