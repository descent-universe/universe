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

        if ( empty($stack) ) {
            $data[$current] = is_array($value) ? array_normalize($value) : $value;
            return $data;
        }

        if ( array_key_exists($current, $data) ) {
            $data[$current] = $implant(is_array($data[$current]) ? $data[$current] : [], $stack);
            return $data;
        }

        $data[$current] = $implant([], $stack);
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

        if ( array_key_exists($current, $data) && ! empty($stack) && is_array($data[$current]) ) {
            $data[$current] = $destroyer($data[$current], $stack);
        }

        if ( array_key_exists($current, $data) && empty($stack) ) {
            unset($data[$current]);
        }

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
    $normalizer = function($inbound) use (&$normalizer)
    {
        if ( ! is_array($inbound) ) {
            return $inbound;
        }

        $outbound = [];

        foreach ( $inbound as $key => $value ) {
            if ( false !== strpos($key, '.') ) {
                $outbound = array_extend($outbound, $key, $value);
                continue;
            }

            $outbound[$key] = $normalizer($value);
        }

        return $outbound;
    };

    return $normalizer($array);
}