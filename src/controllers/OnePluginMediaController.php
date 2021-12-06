<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\controllers;

use Craft;
use yii\web\Response;
use craft\db\Paginator;
use craft\web\Controller;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;
use oneplugin\onepluginmedia\records\OnePluginMediaCategory;


class OnePluginMediaController extends Controller
{
    public $plugin;
    protected $allowAnonymous = true;
    const QUERY_PAGE_SIZE = 30;

    public function init()
    {
        $this->plugin = OnePluginMedia::$plugin;
        parent::init();
    }

    public function actionIndex()
    {
        $url = "one-plugin-media/settings";
        return $this->redirect($url);
    }

    public function actionImageEdit(): Response
    {
        $this->requirePostRequest();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $asset = Craft::$app->getAssets()->getAssetById($assetId);
        $settings = $this->plugin->getSettings();
        $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($asset) !== null;
        return $this->renderTemplate('one-plugin-media/image_edit/index', array_merge(
            [
                'plugin' => $this->plugin,
                'settings' => $settings,
                'asset' => $asset,
                'previewable' => $previewable
            ],
            Craft::$app->getUrlManager()->getRouteParams())
        );
    
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
}
