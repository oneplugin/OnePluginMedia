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
class OnePluginMediaVersion extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%onepluginmedia_config}}';
    }

    public static function latest_version()
    {
        $version = OnePluginMediaVersion::find()
                ->where(['id' => 1])->limit(1)
                ->all();
        if (count($version) > 0 ) {
            return $version[0]['content_version_number'];
        }
        else{
            return '1.0';
        }
    }
}