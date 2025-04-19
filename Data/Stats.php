<?php

namespace nadlib\Data;

/**
 * Class Stats
 * @package nadlib\Data
 * @see https://github.com/Oefenweb/php-statistics
 */
class Stats
{

	// Function to calculate square of value - mean
	public static function sd_square($x, $mean): int|float
	{
		return pow($x - $mean, 2);
	}

	// Function to calculate standard deviation (uses sd_square)
	public static function sd(array $array): float
	{
		// square root of sum of squares devided by N-1
		return sqrt(
			array_sum(
				array_map(
					['self', "sd_square"],
					$array,
					array_fill(0, count($array),
						(array_sum($array) / count($array)))
				)
			) / (count($array) - 1)
		);
	}

	public static function mean(array $set): int|float
	{
		return array_sum($set) / count($set);
	}

	public static function cv(array $set): int|float
	{
		return self::sd($set) / self::mean($set);
	}

	/**
     * @param int $numBuckets
     * @return array
     * Buckets [-12...0...+12]
     */
    public static function buildDistribution(array $set, $numBuckets = 25): array
	{
		$buckets = array_fill(0, $numBuckets + 1, 0);
//		debug($buckets);
		$min = min($set);
		$max = max($set);
//		$mode = ($max - $min) / 2;

		foreach ($set as $value) {
			$key = ($value - $min) / ($max - $min) * $numBuckets;
			$buckets[$key] += 1;
		}

		return $buckets;
	}


}
