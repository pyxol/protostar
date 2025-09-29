<?php
	namespace Protostar\Utils;
	
	class Time {
		/**
		 * Check if the given time is in the future.
		 * @param string|int $time A string parsable by strtotime or a Unix timestamp.
		 * @return bool
		 */
		public static function isFuture(string|int $time): bool {
			$timestamp = is_string($time) ? strtotime($time) : $time;
			
			return $timestamp > time();
		}
		
		/**
		 * Get the number of days since the given date.
		 * @param string|int $time A string parsable by strtotime or a Unix timestamp.
		 * @return int
		 */
		public static function daysSince(string|int $time): int {
			$now = new \DateTime();
			$then = is_string($time) ? new \DateTime($time) : (new \DateTime())->setTimestamp($time);
			$interval = $now->diff($then);
			
			return (int)$interval->format('%a');
		}
	}