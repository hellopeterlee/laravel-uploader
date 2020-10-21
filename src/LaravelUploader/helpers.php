<?php

/*
 * This file is part of the overtrue/laravel-uploader.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (!function_exists('uploader_strategy')) {
    /**
     * Get uploader strategy config.
     *
     * @param string $strategy
     *
     * @return array
     */
    function uploader_strategy($strategy)
    {
        $default = config('uploader.strategies.default', []);
        $userConfig = config('uploader.strategies.' . $strategy, []);
        $config = array_merge_recursive_distinct($default, $userConfig);
        return array_merge([
            'filters' => [],
        ], $config);
    }
}

if (!function_exists('array_merge_recursive_distinct')) {
    /**
     * Array merge recursive distinct.
     *
     * @param array &$array1
     * @param array &$array2
     *
     * @return array
     */
    function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
