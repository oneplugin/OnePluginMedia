<?php
/**
 * OnePlugin Media plugin for Craft CMS 3.x
 *
 * Build a Craft CMS site with one field!
 *
 * @link      https://github.com/oneplugin/
 * @copyright Copyright (c) 2021 OnePlugin
 */
namespace oneplugin\onepluginmedia\helpers;

class StringHelper
{
    
    public static function replaceValues($string, $values): string
    {
        foreach (self::flattenArrayValues($values) as $key => $value) {
            $string = (string) preg_replace("/\{$key\}/", $value, $string);
        }

        return $string;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    public static function flattenArrayValues(array $values): array
    {
        $return = [];

        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                $value = implode(', ', $value);
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Splits an underscored of camelcased string into separate words
     *
     * @param string $string
     *
     * @return string
     */
    public static function humanize($string): string
    {
        $string = strtolower(trim(preg_replace(['/([A-Z])/', "/[_\\s]+/"], ['_$1', ' '], $string)));

        return $string;
    }

    /**
     * Turns every first letter in every word in the string into a camel cased letter
     *
     * @param string $string
     * @param string $delimiter
     *
     * @return string
     */
    public static function camelize($string, $delimiter = ' '): string
    {
        $stringParts = explode($delimiter, $string);
        $camelized = array_map('ucwords', $stringParts);

        return implode($delimiter, $camelized);
    }

    /**
     * Walk through the array and create an HTML tag attribute string
     *
     * @param array $array
     *
     * @return string
     */
    public static function compileAttributeStringFromArray(array $array): string
    {
        $attributeString = '';

        foreach ($array as $key => $value) {
            if (null === $value || (\is_bool($value) && $value)) {
                $attributeString .= "$key ";
            } else if (!\is_bool($value)) {
                $attributeString .= "$key=\"$value\" ";
            }
        }

        return $attributeString ? ' ' . $attributeString : '';
    }

    /**
     * Takes any items separated by a whitespace or any of the following `|,;` in a string
     * And returns an array of the items
     *
     * @param string $string
     *
     * @return array
     */
    public static function extractSeparatedValues(string $string): array
    {
        $string = preg_replace('/[\s|,;]+/', '<|_|_|>', $string);

        $items = explode('<|_|_|>', $string);
        $items = array_filter($items);
        $items = array_unique($items);
        $items = array_values($items);

        return $items;
    }

    /**
     * @param string       $glue
     * @param array|string $data
     *
     * @return string
     */
    public static function implodeRecursively($glue, $data): string
    {
        if (!is_array($data)) {
            return $data;
        }

        $pieces = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $pieces[] = self::implodeRecursively($glue, $item);
            } else {
                $pieces[] = $item;
            }
        }

        return implode($glue, $pieces);
    }
}
