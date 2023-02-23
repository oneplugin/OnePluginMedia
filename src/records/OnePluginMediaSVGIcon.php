<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
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
