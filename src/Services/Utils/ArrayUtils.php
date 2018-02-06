<?php

namespace App\Services\Utils;

/**
 * Class ArrayUtils
 */
class ArrayUtils
{
    /**
     * Format a given array with two parameters into a better array
     *
     * @param array $array - Array to format
     *
     * @return array - Formatted array
     */
    public static function formatTotalArray(array $array)
    {
        // Initialize
        $formattedArray = [];

        // Check array to format
        if (empty($array)) {
            return $formattedArray;
        }

        // Loop on array to format
        foreach ($array as $subArray) {
            // Initialize: store and remove total
            $total = $subArray['total'];
            unset($subArray['total']);

            // Get key
            $key = array_shift($subArray);

            // Save values in formatted array
            $formattedArray[$key] = (int) $total;
        }

        // Return formatted array
        return $formattedArray;
    }
}
