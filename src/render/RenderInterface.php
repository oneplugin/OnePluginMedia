<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\render;

use oneplugin\onepluginmedia\models\OnePluginMediaAsset;

interface RenderInterface
{
    /**
     * Return an HTML string for the corresponding content type
     *
     * @param OnePluginMediaAsset              $asset
     * @param array               $options
     *
     * @return string
     */
    public function render(OnePluginMediaAsset $asset, array $options): array;
}
