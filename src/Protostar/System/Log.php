<?php
	namespace Protostar\System;
	
	class Log {
		/**
		 * Log levels
		 * @var array<string, int>
		 */
		const LOG_LEVELS = [
			'DEBUG' => 0,
			'INFO' => 1,
			'WARNING' => 2,
			'ERROR' => 3,
			'CRITICAL' => 4
		];
		
		/**
		 * Write a message to the system log
		 * @param string $level The log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
		 * @param mixed $string The message to log
		 * @return void
		 */
		public static function write(string $level, mixed $string): void {
			$level = strtoupper(trim($level));
			
			if(!isset(self::LOG_LEVELS[ $level ])) {
				$level = 'INFO';
			}
			
			$logFile = LOG_PATH . date("Y-m-d") .'.log';
			
			$datetime = date('Y-m-d H:i:s');
			
			file_put_contents($logFile, "[". $datetime ."] ". $level .": ". (is_array($string)?print_r($string, true):$string) ."\n", FILE_APPEND);
			
			try {
				db_query("
					INSERT INTO `log`
					SET
						log.datetime = '". esc_sql($datetime) ."',
						log.level = '". esc_sql($level) ."',
						log.message = '". esc_sql( (is_array($string)?json_encode($string):$string) ) ."',
						log.backtrace = '". esc_sql(json_encode(debug_backtrace())) ."'
				");
			} catch(\Throwable $e) {
				// do nothing
			}
		}
		
		/**
		 * Record a debug message in the log file.
		 * @param mixed $message The debug message to log.
		 * @return void
		 */
		public static function debug(mixed $message): void {
			self::write('DEBUG', $message);
		}
		
		/**
		 * Record an info message in the log file.
		 * @param mixed $message The info message to log.
		 * @return void
		 */
		public static function info(mixed $message): void {
			self::write('INFO', $message);
		}
		
		/**
		 * Record a warning message in the log file.
		 * @param mixed $message The warning message to log.
		 * @return void
		 */
		public static function warning(mixed $message): void {
			self::write('WARNING', $message);
		}
		
		/**
		 * Record an error message in the log file.
		 * @param mixed $message The error message to log.
		 * @return void
		 */
		public static function error(mixed $message): void {
			self::write('ERROR', $message);
		}
		
		/**
		 * Record a critical message in the log file.
		 * @param mixed $message The critical message to log.
		 * @return void
		 */
		public static function critical(mixed $message): void {
			self::write('CRITICAL', $message);
		}
	}