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

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $pluginName = 'OnePlugin Media';
    public $svgStrokeColor = '#66a1ee';
    public $svgStrokeWidth = 50;
    public $opOutputFormat = 'webp';
    public $opImageVariants = [
            [
            "opWidth" => "1600",
            "opQuality" => "90"
            ],
            [
                "opWidth" => "1200",
                "opQuality" => "90"
            ],
            [
                "opWidth" => "992",
                "opQuality" => "85"
            ],
            [
                "opWidth" => "768",
                "opQuality" => "80"
            ],
            [
                "opWidth" => "576",
                "opQuality" => "75"
            ],
    ];
    public $opUpscale = false;

    public $opImageTag = 'picture';
    
    public $mapsAPIKey = '';

    public $enableCache = true;

    public $aIconDataAsHtml = true;

    public $newContentPackAvailable = false;

    public $opSettingsHash = 'f9b3ab9dab8d9967db789dec586cafa6';

    public function rules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'svgStrokeColor','svgStrokeWidth'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['svgStrokeWidth'], 'number', 'integerOnly' => true];
        $rules[] = [['svgStrokeWidth'], 'number', 'min' => 1];
        $rules[] = [['svgStrokeWidth'], 'number', 'max' => 100];

        return $rules;
    }
}
