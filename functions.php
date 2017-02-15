<?php
/**
 * This file is part of the Descent Framework.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

/**
 * gets a array value by the provided path query.
 *
 * @api 1.0.0
 *
 * @param array $array the array to work on
 * @param string $query the array path delimited by a dot ('.')
 * @param null|mixed $default the default value returned when the path is not known
 * @return mixed the requested value
 */
function array_fetch(array $array, string $query, $default = null)
{
    $capture = function($inbound, array $stack) use ($default, &$capture)
    {
        $current = array_shift($stack);

        if ( empty(trim($current)) && empty($stack) ) {
            return $inbound;
        }

        if ( ! is_array($inbound) ) {
            return $default;
        }

        if ( array_key_exists($current, $inbound) && ! empty($stack) ) {
            return $capture($inbound[$current], $stack);
        }

        return $inbound[$current];
    };

    return empty($query) ? $array : $capture($array, explode('.', $query));
}

/**
 * sets a array value by the provided path query.
 *
 * @api 1.0.0
 *
 * @param array $array the array to work with
 * @param string $query the array path delimited by a dot ('.')
 * @param mixed $value the value to set
 * @return array the modified array (copy)
 */
function array_extend(array $array, string $query, $value): array
{
    $implant = function(array $data, array $stack) use ($value, &$implant): array
    {
        $current = array_shift($stack);

        if ( empty(trim($current)) && empty($stack) ) {
            $data[] = $value;
            return $data;
        }

        if ( array_key_exists($current, $data) && ! empty($stack) ) {
            $data = $implant(is_array($data[$current]) ? $data[$current] : [], $stack);
            return $data;
        }

        $data[$current] = $value;
        return $data;
    };

    return $implant($array, explode('.', $query));
}

/**
 * destroys a array value by the provided path query.
 *
 * @api 1.0.0
 *
 * @param array $array the array to work with
 * @param string $query the array path delimited by a dot ('.')
 * @return array the modified array (copy)
 */
function array_exclude(array $array, string $query): array
{
    $destroyer = function(array $data, array $stack) use (&$destroyer): array
    {
        $current = array_shift($stack);

        if ( empty(trim($current)) ) {
            return $data;
        }

        if ( array_key_exists($current, $data) && ! empty($stack) && ! is_array($data[$current]) ) {
            return $data;
        }

        if ( array_key_exists($current, $data) && ! empty($stack) && is_array($data[$current]) ) {
            $data = $destroyer($data[$current], $stack);
            return $data;
        }

        unset($data[$current]);
        return $data;
    };

    return $destroyer($array, explode('.', $query));
}

/**
 * normalizes the array keys of a provided array (eases possible dot ('.') delimited notations).
 *
 * @api 1.0.0
 *
 * @param array $array
 * @return array
 */
function array_normalize(array $array): array
{
    $normalizer = function(array $inbound, array $stack) use (&$normalizer): array
    {
        if ( ! empty($stack) ) {
            $current = array_shift($stack);

            return [
                $current => $normalizer($inbound, $stack)
            ];
        }

        $outbound = [];

        foreach ( $inbound as $key => $value ) {
            if ( false !== strpos($key, '.') ) {
                $outbound[$key] = $value;
                continue;
            }

            $stack = explode('.', $key);
            $current = array_shift($stack);
            $outbound[$current] = $normalizer($value, $stack);
        }

        return $outbound;
    };

    return $normalizer($array);
}