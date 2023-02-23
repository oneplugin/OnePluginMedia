<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */


namespace oneplugin\onepluginmedia\jobs;

use Craft;
use craft\queue\BaseJob;
use oneplugin\onepluginmedia\OnePluginMedia;
use oneplugin\onepluginmedia\records\OnePluginMediaVersion;

class ContentSyncJob extends BaseJob
{

    public function execute($queue)
    {
        $settings = OnePluginMedia::$plugin->getSettings();
        if( $settings->newContentPackAvailable ){
            $this->addJob();
            return;
        }
        $version = OnePluginMediaVersion::latest_version();
        $response = OnePluginMedia::$plugin->onePluginMediaService->checkForUpdates($version);
        if( $response['updates'] ){
            Craft::$app->plugins->savePluginSettings(OnePluginMedia::$plugin, ['newContentPackAvailable'=>true]);
        }
        $this->addJob();
    }

    private function addJob(){
        //This function adds a job for checking availability of new content after 24 hours.

        $queue = Craft::$app->getQueue();
        $jobId = $queue->priority(1024)
                        ->delay(6 * 60 * 60)
                        ->ttr(300)
                        ->push(new ContentSyncJob([
            'description' => Craft::t('one-plugin-media', 'OnePlugin Media - Job for checking availability of new content packs')
        ]));
    }
}
