<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\gql\models;

use Craft;
use craft\base\Model;
use craft\gql\TypeLoader;
use craft\gql\base\GqlTypeTrait;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use oneplugin\onepluginmedia\gql\resolvers\OnePluginMediaResolver;
use oneplugin\onepluginmedia\records\OnePluginMediaOptimizedImage;
use oneplugin\onepluginmedia\models\OnePluginMediaOptimizedImage as OnePluginMediaOptimizedImageModel;

class ImageGql extends Model
{
    use GqlTypeTrait;

    public $iconData = null;

    public static function getName($context = null): string
    {
        return 'OnePluginMedia_Image';
    }

    static public function getType(): Type
    {
        $typeName = self::getName();
        $type = GqlEntityRegistry::getEntity($typeName)
          ?: GqlEntityRegistry::createEntity($typeName, new OnePluginMediaResolver([
          'name'   => static::getName(),
          'fields' => self::class . '::getFieldDefinitions',
          'description' => 'The interface implemented by OnepluginMedia SVG type.',
          ]));
        

        TypeLoader::registerType(static::getName(), function () use ($type) {
          return $type;
        });
      
      return $type;
    }

    /**
     * @return array
     */
    public static function getFieldDefinitions(): array {
      return [
        'alt' => [
            'name' => 'alt',
            'type' => Type::string(),
            'description' => 'Alternative text for the image.',
        ],
        'srcset' => [
            'name' => 'srcset',
            'type' => Type::string(),
            'description' => 'Return a string of image URLs and their sizes',
        ],
        'srcsetWebP' => [
            'name' => 'srcsetWebP',
            'type' => Type::string(),
            'args' => [
                'webp' => [
                    'name' => 'webp',
                    'type' => Type::boolean(),
                    'description' => 'If true, returns webp images.'
                ],
            ],
            'description' => 'Return a string of image URLs and their sizes',
        ],
        'filename' => [
            'name' => 'filename',
            'type' => Type::nonNull(Type::string()),
            'description' => 'The filename of the asset file.',
        ],
        'extension' => [
            'name' => 'extension',
            'type' => Type::nonNull(Type::string()),
            'description' => 'The file extension for the asset file.',
        ],
        'hasFocalPoint' => [
            'name' => 'hasFocalPoint',
            'type' => Type::nonNull(Type::boolean()),
            'description' => 'Whether a user-defined focal point is set on the asset.',
        ],
        'focalPoint' => [
            'name' => 'focalPoint',
            'type' => Type::listOf(Type::float()),
            'description' => 'The focal point represented as an array with `x` and `y` keys, or null if it’s not an image.',
        ],
        'size' => [
            'name' => 'size',
            'type' => Type::string(),
            'description' => 'The file size in bytes.',
        ],
        'height' => [
            'name' => 'height',
            'type' => Type::int(),
            'description' => 'The height in pixels or null if it’s not an image.',
        ],
        'width' => [
            'name' => 'width',
            'type' => Type::int(),
            'description' => 'The width in pixels or null if it’s not an image.'
        ],
        'url' => [
            'name' => 'url',
            'type' => Type::string(),
            'description' => 'The full URL of the asset. This field accepts the same fields as the `transform` directive.',
        ],
        'mimeType' => [
            'name' => 'mimeType',
            'type' => Type::string(),
            'description' => 'The file’s MIME type, if it can be determined.',
        ],
      ];
    }

    public function __construct($value)
    {
        if( $value != null){
            $this->iconData = (array)json_decode($value,true);
        }
        else{
            $this->iconData = [];
        }
    }

    public function getAlt(){
        return $this->iconData['alt'];
    }
    public function getSrcset(): string
    {
        $srcset = '';
        $assets = OnePluginMediaOptimizedImage::find()->where(['assetId' => $this->iconData['id']])->all();
        if( count($assets) > 0 && !empty($assets[0]['content'])){
            $optimizedImage = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
            $imageUrls = null;
            
            if( $optimizedImage->extension != 'webp' ){
                $imageUrls = $optimizedImage->imageUrls;
            }
            else{
                if( $optimizedImage->fallbackImageUrls && sizeof($optimizedImage->fallbackImageUrls) > 0 ){
                    $imageUrls = $optimizedImage->fallbackImageUrls;
                }
                else{
                    $imageUrls = $optimizedImage->imageUrls;
                }
            }
            foreach ($imageUrls as $key => $value) {
                if( !empty($value['url']) ){
                    $srcset .= $value['url'] . ' ' . $key . 'w, ';
                }
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }

    public function getSrcsetWebP(): string
    {
        $srcset = '';
        $assets = OnePluginMediaOptimizedImage::find()->where(['assetId' => $this->iconData['id']])->all();
        if( count($assets) > 0 && !empty($assets[0]['content'])){
            $optimizedImage = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
            if( $optimizedImage->extension == 'webp' ){
                $optimizedImage = new OnePluginMediaOptimizedImageModel($assets[0]['content']);
                $imageUrls = $optimizedImage->imageUrls;
                foreach ($imageUrls as $key => $value) {
                    if( !empty($value['url']) ){
                        $srcset .= $value['url'] . ' ' . $key . 'w, ';
                    }
                }
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }

    public function getFilename(): string
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getFilename();
        }
        return null;
    }

    public function getExtension(): string
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getExtension();
        }
        return null;
    }

    public function getHasFocalPoint(): bool
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getHasFocalPoint();
        }
        return null;
    }

    public function getFocalPoint(): array|string|null
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getFocalPoint();
        }
        return null;
    }
    public function getSize(): array|string|null
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getFormattedSize();
        }
        return null;
    }
    
    public function getHeight(): ?int
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getHeight();
        }
        return null;

    }

    public function getWidth(): ?int
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getWidth();
        }
        return null;
    }

    public function getUrl(): ?string
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getUrl();
        }
        return null;
    }
    
    public function getMimeType(): ?string
    {
        $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
        if( $imageAsset ){
            return $imageAsset->getMimeType();
        }
        return null;
    }
}
