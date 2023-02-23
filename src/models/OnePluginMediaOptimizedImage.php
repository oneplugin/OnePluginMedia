<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\models;

class OnePluginMediaOptimizedImage{

    public $name;

    public $extension;

    public $width;

    public $height;

    public $originalUrl = '';

    public $imageUrls = [];
    
    public $fallbackImageUrls = [];

    public $placeHolder = '';

    public $errors = [];

    public function __construct($value)
    {
        if($this->validateJson($value)){
            $json = (array)json_decode($value,true);
            $this->name = $json['name'];
            $this->extension = $json['extension'];
            $this->width = $json['width'];
            $this->height = $json['height'];
            $this->originalUrl = $json['originalUrl'];
            $this->placeHolder = $json['placeHolder'];
        	$this->imageUrls = $json['imageUrls'];
            if( isset($json['fallbackImageUrls'])){
                $this->fallbackImageUrls = $json['fallbackImageUrls'];
            }
        } else {

        }
    }

    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }

}
