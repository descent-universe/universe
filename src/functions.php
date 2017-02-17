<?php
/**
 * This file is part of the Descent Framework.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

# ---- Array Functions

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
 * pings an array query path for existance.
 *
 * @param array $array
 * @param string $query
 * @return bool
 */
function array_ping(array $array, string $query): bool
{
    $ping = function($inbound, array $stack) use (&$ping): bool
    {
        $current = array_shift($stack);

        if ( empty($stack) ) {
            return true;
        }

        if ( is_array($inbound) && array_key_exists($current, $inbound) ) {
            return $ping($inbound[$current], $stack);
        }

        return false;
    };

    return empty($query) ? false : $ping($array, explode('.', $query));
}

/**
 * guarantees that the provided query path is an array.
 *
 * @param array $array
 * @param string $query
 * @return array
 */
function array_touch(array $array, string $query): array
{
    return array_extend($array, $query, []);
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

# --- Callback functions

/**
 * Extracts a closure from the provided callback.
 *
 * @param callable $callback
 * @return Closure
 */
function encloseCallback(callable $callback): Closure
{
    if ( class_exists(Closure::class) && method_exists(Closure::class, 'fromCallable') ) {
        return Closure::fromCallable($callback);
    }

    if ( is_string($callback) && false !== strpos($callback, '::') ) {
        list($class, $method) = explode('::', $callback);
        return (new ReflectionClass($class))->getMethod($method)->getClosure();
    }

    if ( is_array($callback) ) {
        list($object, $method) = $callback;
        return (new ReflectionClass($object))->getMethod($method)->getClosure($object);
    }

    if ( is_object($callback) && ! $callback instanceof Closure ) {
        return (new ReflectionClass($callback))->getMethod('__invoke')->getClosure($callback);
    }

    return (new ReflectionFunction($callback))->getClosure();
}

/**
 * Extracts a closure from the provided masked callable.
 *
 * @param string $pattern
 * @param string $methodSeparator
 * @param string $namespace
 * @return Closure
 */
function encloseCallbackPattern(string $pattern, $methodSeparator = '@', $namespace = "\\"): Closure
{
    if ( false === strpos($pattern, $methodSeparator) ) {
        throw new LogicException(
            "The provided pattern does not contain the provided method separator ({$methodSeparator})"
        );
    }

    return encloseCallback(explode($methodSeparator, ltrim($namespace, "\\").$pattern));
}