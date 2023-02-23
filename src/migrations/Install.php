<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\migrations;

use Craft;

use craft\db\Migration;
use craft\helpers\Json;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\records\OnePluginMediaSVGIcon;
use oneplugin\onepluginmedia\records\OnePluginMediaVersion;
use oneplugin\onepluginmedia\records\OnePluginMediaCategory;

class Install extends Migration
{
    public $driver;

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    protected function createTables()
    {
        $this->dropTableIfExists('{{%onepluginmedia_config}}');
        $this->createTable('{{%onepluginmedia_config}}', [
            'id' => $this->primaryKey(),
            'content_version_number' => $this->string(256)->notNull(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);
        $this->dropTableIfExists('{{%onepluginmedia_category}}');
        $this->createTable('{{%onepluginmedia_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(256)->notNull(),
            'type' => $this->string(256)->notNull(),
            'parent_id' => $this->integer(),
            'count' => $this->integer(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginmedia_svg_icon}}');
        $this->createTable('{{%onepluginmedia_svg_icon}}', [
            'id' => $this->primaryKey(),
            'category' => $this->integer()->notNull(),
            'name' => $this->string(256)->notNull(),
            'title' => $this->string(256)->notNull(),
            'description' => $this->text(),
            'data' => $this->mediumText(),
            'tags' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginmedia_optimized_image}}');
        $this->createTable('{{%onepluginmedia_optimized_image}}', [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'content' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);

        $this->dropTableIfExists('{{%onepluginmedia_svg_icon_packs}}');
        $this->createTable('{{%onepluginmedia_svg_icon_packs}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'handle' => $this->string(),
            'category' => $this->integer()->notNull(),
            'count' => $this->string()->notNull(),
            'dateArchived' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    protected function createIndexes()
    {
        $this->createIndex(null, '{{%onepluginmedia_config}}', 'id', true);
        $this->createIndex(null, '{{%onepluginmedia_svg_icon}}', 'id', true);
        $this->createIndex(null, '{{%onepluginmedia_category}}', 'id', true);
        $this->createIndex(null, '{{%onepluginmedia_optimized_image}}', 'id', true);
        $this->createIndex(null, '{{%onepluginmedia_optimized_image}}', 'assetId', true);
        $this->createIndex(null, '{{%onepluginmedia_svg_icon_packs}}', 'category', false);
        
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%onepluginmedia_svg_icon}}', ['category'], '{{%onepluginmedia_category}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%onepluginmedia_svg_icon_packs}}', ['category'], '{{%onepluginmedia_category}}', ['id'], 'CASCADE', null);
    }

    protected function insertDefaultData()
    {
        $command = $this->db->createCommand()->insert(OnePluginMediaVersion::tableName(), [
            'content_version_number' => '1.0'
        ]);
        $command->execute();

        $dir = OnePluginMedia::getInstance()->getBasePath();
        $path = $dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'data.json';
        $data = Json::decode(file_get_contents($path));
        $latest_version = '';
        foreach ($data as $version => $value) {
            $latest_version = $version;
            $categories = $value['categories'];
            $svgIcons = $value['svg'];

            foreach ($categories as $category) {
                $type = 'svg';
                $parent_id = 0;
                if( !empty($category['parent_id'])){
                    $parent_id = $category['parent_id'];
                }
                $command = $this->db->createCommand()->insert(OnePluginMediaCategory::tableName(), [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'type' => $type,
                    'count' => 0,
                    'parent_id' => $parent_id,
                ]);
                $command->execute();
            }

            foreach ($svgIcons as $svgIcon) {
                $command = $this->db->createCommand()->insert(OnePluginMediaSVGIcon::tableName(), [
                    'category' => $svgIcon['cid'],
                    'name' => $svgIcon['fname'],
                    'title' => $svgIcon['name'],
                    'description' => ' ',
                    'data' => $svgIcon['data'],
                    'tags' => $svgIcon['tags']
                ]);
                $command->execute();
            }
        }

        $command = $this->db->createCommand()->update(OnePluginMediaVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();

        $this->db->createCommand("update onepluginmedia_category set count = (select count(id) from onepluginmedia_svg_icon where onepluginmedia_svg_icon.category = onepluginmedia_category.id) where onepluginmedia_category.type = 'svg'")->execute();
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%onepluginmedia_config}}');
        $this->dropTableIfExists('{{%onepluginmedia_svg_icon_packs}}');
        $this->dropTableIfExists('{{%onepluginmedia_svg_icon}}');
        $this->dropTableIfExists('{{%onepluginmedia_optimized_image}}');
        $this->dropTableIfExists('{{%onepluginmedia_category}}');
        $this->db->createCommand("delete from " . Craft::$app->getQueue()->tableName . " where description like 'OnePlugin Media%' ")->execute();
    }
}
