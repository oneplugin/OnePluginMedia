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

use DOMElement;
use DOMDocument;
use yii\web\Response;
use craft\web\Controller;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\helpers\EvalMath;
use oneplugin\onepluginmedia\elements\SVGIconPack;
use oneplugin\onepluginmedia\helpers\StringHelper;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;

class SvgIconsController extends Controller
{

    public $plugin;

    public function init():void
    {
        $this->plugin = OnePluginMedia::$plugin;
        parent::init();
    }

    public function actionIndex(): Response
    {
        $this->requireAdmin();
        $settings = $this->plugin->getSettings();

        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/dist',
            true
        );

        return $this->renderTemplate('one-plugin-media/svgicons/index', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionNew(): Response
    {
        $this->requireAdmin();
        $settings = $this->plugin->getSettings();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/dist',
            true
        );
        
        Craft::$app->getView()->registerCssFile($baseAssetsUrl . '/css/svgicon.css');
        return $this->renderTemplate('one-plugin-media/svgicons/_new', array_merge(
            [
                'plugin' => $this->plugin,
                'iconPack' => new SVGIconPack(),
                'settings' => $settings
            ],
            Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $name = $request->getParam('name');
        $files = [];
        $errors = [];
        if( !array_key_exists('icons',$_FILES ) ){
            $errors[] = 'There should be atleast one icon in the pack.';
        }
        else{
            foreach ($_FILES['icons']['error'] as $key => $error)
            {
                if (!$error)
                {
                    $filename = $_FILES['icons']['name'][$key];
                    $file = $_FILES['icons']['tmp_name'][$key];
                    $svg = $this->processSVGFile(file_get_contents($file));
                    $files[$filename] = $svg;
                }
            }
        }
        $iconPack = new SVGIconPack();
        $iconPack->name = $name;
        $iconPack->title = $name;
        $iconPack->description = $name;
        $iconPack->icons = $files;
        $iconPack->count = sizeof($files);
        if( !empty($name)){
            $iconPack->handle = StringHelper::camelize($name) . rand(1000,9999);
        }
        else{
            $errors[] = 'Icon pack name cannot be empty';
        }

        $transaction = Craft::$app->db->beginTransaction();
        $success = $iconPack->validate(null, false) && Craft::$app->elements->saveElement($iconPack) ;
        if( !$success){
            $transaction->rollBack();
            
            Craft::info('Icon pack not saved due to validation error.', __METHOD__);
            if (!$iconPack->hasErrors('title')) {
                $iconPack->addErrors(['name' => ['Icon pack name cannot be empty']]);
            }
            $json = [
                'error' => $errors,
            ];
            Craft::$app->getResponse()->setStatusCode(500);
            return $this->asJson($json);
        }
        else{
            $transaction->commit();
            Craft::$app->getSession()->setNotice(Craft::t('one-plugin-media', 'SVG Icon Pack added successfully.'));
            $json = [
                'success' => true,
            ];
            return $this->asJson($json);
        }    
        
    }

    public function actionEdit($iconPackId){

        $this->requireAdmin();
        $settings = $this->plugin->getSettings();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginmedia/assetbundles/onepluginmedia/dist',
            true
        );
        
        Craft::$app->getView()->registerCssFile($baseAssetsUrl . '/css/svgicon.css');
        $svgIconPack = SVGIconPack::findOne($iconPackId);
        $icons = OnePluginMediaSVGIcon::find()->where(['category' => $svgIconPack->category])->all();
        $m = new EvalMath;
        foreach ($icons as $icon) {
            $svg = $icon->data;
            $svg = str_replace('{color}',$settings->svgStrokeColor,$svg);
            $svg = str_replace('{stroke-width}',$settings->svgStrokeWidth,$svg);
            $formula = $this->extract_formula($svg,"c:","/c:");
            while(strlen($formula) > 0 ){
                $result = $m->evaluate($formula);
                $svg = str_replace('c:' . $formula . '/c:',$result,$svg);
                $formula = $this->extract_formula($svg,"c:","/c:");
            }
            $icon->data = $svg;
        }
        return $this->renderTemplate('one-plugin-media/svgicons/_edit', array_merge(
            [
                'plugin' => $this->plugin,
                'iconPack' => $svgIconPack,
                'icons' => $icons,
                'settings' => $settings
            ],
            Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    private function implode_all($glue, $arr){            
        for ($i=0; $i<count($arr); $i++) {
            if (@is_array($arr[$i])) 
                $arr[$i] = $this->implode_all ($glue, $arr[$i]);
        }            
        return implode($glue, $arr);
    }

    private function processSVGFile($input) {
        $doc = new DOMDocument();
        $svgDoc = new DOMDocument();
        $ignore = ['xmlns','width','height'];
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = true;
        $doc->loadXML($input);
        if( $doc->getElementsByTagName('svg') && $doc->getElementsByTagName('svg')->length > 0){
            $element = $doc->getElementsByTagName('svg')->item(0);
            if ($element->hasAttributes()) {
                foreach ($ignore as $name) {
                    $element->removeAttribute($name);
                }
            }
            $this->traverse($element);
            $svg = $doc->saveXML($element);
            return $svg;
        }
        return $input;
    }

    function traverse( DOMElement $node, $level=0 ){
        $this->handle_node( $node, $level );
        if ( $node->hasChildNodes() ) {
            $children = $node->childNodes;
            foreach( $children as $kid ) {
                if ( $kid->nodeType == XML_ELEMENT_NODE ) {
                    $this->traverse( $kid, $level+1 );
                }
            }
        }
    }

    function handle_node( DOMElement $child, $level ) {
        if( $child->getAttribute("style") ){
            $style = $child->getAttribute("style");
            preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $style, $arrStyles, PREG_SET_ORDER);
            foreach ($arrStyles as $match) {
                $results[strtolower($match[1])] = $match[2];
            }
            if( array_key_exists('stroke', $results)){
                $results['stroke'] = '{color}';
            }
            if( array_key_exists('color', $results)){
                $results['color'] = '{color}';
            }
            if( array_key_exists('stroke-width', $results)){
                $width = $results["stroke-width"];
                preg_match_all('!\d+\.*\d*!', $width, $matches);
                if( sizeof($matches) > 0 ){
                    $newWidth = 'c:( ' . $matches[0][0] . ' * {stroke-width} / 50 )/c:';
                    $results['stroke-width'] = str_replace($matches[0],$newWidth,$width);
                }
            }
            $result = array_map(function($k, $v){
                return "$k:$v";
            }, array_keys($results), array_values($results));
            $child->removeAttribute('style');
            $child->setAttribute('style', join(';',$result));
        }
        if( $child->getAttribute("stroke") ){
            $child->setAttribute('stroke', '{color}');
        }
        if( $child->getAttribute("stroke-width") ){
            $width = $child->getAttribute("stroke-width");
            preg_match_all('!\d+\.*\d*!', $width, $matches);
            if( sizeof($matches) > 0 ){
                $newWidth = 'c:( ' . $matches[0][0] . ' * {stroke-width} / 50 )/c:';
                $child->setAttribute('stroke-width', str_replace($matches[0],$newWidth,$width));
            }
        }
    }

    function extract_formula($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}
