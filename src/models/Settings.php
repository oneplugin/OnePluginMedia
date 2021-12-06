<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\models;

use craft\base\Model;


class Settings extends Model
{
    public $pluginName = 'OnePlugin Media';
    public $svgStrokeColor = '#66a1ee';
    public $enableCache = true;

    public function rules()
    {
        return [
            
        ];
    }
}
