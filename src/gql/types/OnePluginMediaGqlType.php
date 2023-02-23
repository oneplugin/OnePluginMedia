<?php

/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * OnePlugin Media lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginmedia\gql\types;

use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use craft\gql\base\GeneratorInterface;
use GraphQL\Type\Definition\InputObjectType;
use oneplugin\onepluginmedia\gql\models\ImageGql;
use oneplugin\onepluginmedia\gql\models\SVGIconGql;
use oneplugin\onepluginmedia\gql\resolvers\OnePluginMediaResolver;
/**
 * 
 */
class OnePluginMediaGqlType implements GeneratorInterface
{
  /**
   * @return string
   */
    public static function getName($context = null): string
    {
      return 'OnePluginMedia_Field';
    }

  /**
   * @return Type
   */
  public static function generateTypes($context = null): array{

    $tagArgument = GqlEntityRegistry::getEntity("OnePluginMedia_TagArgument") ?: GqlEntityRegistry::createEntity("OnePluginMedia_TagArgument", new InputObjectType([
        'name' => 'Tag Argument',
        'fields' => [
          'class' => [
            'name' => 'class',
            'type' => Type::string(),
          ],
          'size' => [
            'name' => 'size',
            'type' => Type::boolean(),
          ],
          'width' => [
            'name' => 'width',
            'type' => Type::string(),
          ],
          'height' => [
            'name' => 'height',
            'type' => Type::string(),
          ],
          'alt' => [
            'name' => 'alt',
            'type' => Type::string(),
          ],
          'navigationbar' => [
            'name' => 'navigationbar',
            'type' => Type::string(),
          ]
        ]]));

    $typeName = self::getName($context);
    $types = [
      'name' => [
        'name' => 'name',
        'type' => Type::string(),
      ],
      'type' => [
        'name' => 'type',
        'type' => Type::string(),
      ],
      'jsAssets' => [
        'name' => 'jsAssets',
        'type' => Type::listOf(Type::string()),
      ],
      'tag' => [
        'name' => 'tag',
        'type' => Type::string(),
        'args' => [
          'options' => [
              'name' => 'options',
              'type' => Type::listOf($tagArgument),
              'description' => 'If true, returns webp images.'
          ],
        ],
        'description' => 'A `<oneplugin>` tag based on this asset.',
      ],
      'src' => [
        'name' => 'src',
        'type' => Type::string(),
        'description' => 'Returns a `src` attribute value',
      ],
      'image' => [
        'name' => 'image',
        'type' => ImageGql::getType(),
      ],
      'svgIcon' => [
        'name' => 'svgIcon',
        'type' => SVGIconGql::getType(),
      ]
    ];

    $type = GqlEntityRegistry::getEntity($typeName)
        ?: GqlEntityRegistry::createEntity($typeName, new OnePluginMediaResolver([
          'name'   => static::getName(),
          'fields' => function () use ($types) {
            return $types;
          },
          'description' => 'This is the interface implemented by OnePlugin Media.',
        ]));

    TypeLoader::registerType($typeName, function () use ($type) {
        return $type;
    });

    return [$type];
  }

  public static function getFieldDefinitions(): array
    {
      $tagArgument = GqlEntityRegistry::getEntity("OnePluginMedia_TagArgument") ?: GqlEntityRegistry::createEntity("OnePluginMedia_TagArgument", new InputObjectType([
        'name' => 'Tag Argument',
        'fields' => [
          'class' => [
            'name' => 'class',
            'type' => Type::string(),
          ],
          'size' => [
            'name' => 'size',
            'type' => Type::boolean(),
          ],
          'width' => [
            'name' => 'width',
            'type' => Type::string(),
          ],
          'height' => [
            'name' => 'height',
            'type' => Type::string(),
          ],
          'alt' => [
            'name' => 'alt',
            'type' => Type::string(),
          ],
          'navigationbar' => [
            'name' => 'navigationbar',
            'type' => Type::string(),
          ]
        ]]));

        return [
          'name' => [
            'name' => 'name',
            'type' => Type::string(),
          ],
          'type' => [
            'name' => 'type',
            'type' => Type::string(),
          ],
          'jsAssets' => [
            'name' => 'jsAssets',
            'type' => Type::listOf(Type::string()),
          ],
          'tag' => [
            'name' => 'tag',
            'type' => Type::string(),
            'args' => [
              'options' => [
                  'name' => 'options',
                  'type' => Type::listOf($tagArgument),
                  'description' => 'If true, returns webp images.'
              ],
            ],
            'description' => 'A `<oneplugin>` tag based on this asset.',
          ],
          'src' => [
            'name' => 'src',
            'type' => Type::string(),
            'description' => 'Returns a `src` attribute value',
          ],
          'image' => [
            'name' => 'image',
            'type' => ImageGql::getType(),
          ],
          'svgIcon' => [
            'name' => 'svgIcon',
            'type' => SVGIconGql::getType(),
          ]
        ];
    }
}
