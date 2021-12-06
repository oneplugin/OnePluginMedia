<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */

namespace oneplugin\onepluginmedia\records;

use craft\db\ActiveRecord;
class OnePluginMediaSVGIcon extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%onepluginmedia_svg_icon}}';
    }
}