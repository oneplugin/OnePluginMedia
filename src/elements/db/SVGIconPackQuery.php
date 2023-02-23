<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\elements\db;

use craft\helpers\Db;
use craft\elements\db\ElementQuery;

class SVGIconPackQuery extends ElementQuery
{
    public $name;
    public $handle;
    public $formType;
    public $formSettings;

    public function name($value)
    {
        $this->name = $value;
        return $this;
    }

    public function handle($value)
    {
        $this->handle = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('onepluginmedia_svg_icon_packs');

        $this->query->select([
            'onepluginmedia_svg_icon_packs.name',
            'onepluginmedia_svg_icon_packs.handle',
            'onepluginmedia_svg_icon_packs.category',
            'onepluginmedia_svg_icon_packs.count',
            'onepluginmedia_svg_icon_packs.dateUpdated'
        ]);

        if ($this->name) {
            $this->subQuery->andWhere(Db::parseParam('onepluginmedia_svg_icon_packs.name', $this->name));
        }

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('onepluginmedia_svg_icon_packs.handle', $this->handle));
        }

        return parent::beforePrepare();
    }
}
