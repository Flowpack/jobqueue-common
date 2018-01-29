<?php
namespace Flowpack\JobQueue\Common\Utility;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\TypeHandling;
use Neos\Utility\Unicode\Functions as UnicodeUtilityFunctions;

class VariableDumper
{

    public static function dumpValue($value, int $maximumLength = 20): string
    {
        if (is_string($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return self::crop($value, $maximumLength);
        } elseif (is_int($value)) {
            return (string)$value;
        } elseif (is_double($value)) {
            return sprintf('%0.2f', $value);
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            return '[' . TypeHandling::getTypeForValue($value) . ']';
        }
    }

    private static function crop(string $value, int $maximumLength): string
    {
        if (UnicodeUtilityFunctions::strlen($value) > $maximumLength) {
            return UnicodeUtilityFunctions::substr($value, 0, $maximumLength - 1) . '…';
        }
        return $value;
    }
}
