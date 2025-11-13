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
		
		
		public static function isLevelValid(string $level): bool {
			$level = strtoupper(trim($level));
			
			return isset(self::LOG_LEVELS[ $level ]);
		}
		
		
		public static function isLevelAtOrBelow(string $level, string $threshold): bool {
			$level = strtoupper(trim($level));
			$threshold = strtoupper(trim($threshold));
			
			if(!self::isLevelValid($level) || !self::isLevelValid($threshold)) {
				return false;
			}
			
			return self::LOG_LEVELS[ $level ] >= self::LOG_LEVELS[ $threshold ];
		}
		
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
			
			$datetime = date('Y-m-d H:i:s');
			
			// file
			try {
				if(!config('log.file.enabled', false)) {
					throw new \Exception("File logging not enabled");
				}
				
				if(!self::isLevelAtOrBelow($level, config('log.file.level', 'DEBUG'))) {
					throw new \Exception("Log level below file log threshold");
				}
				
				//$logFile = LOG_PATH . date("Y-m-d") .'.log';
				$logDirPath = config('log.file.path', null);
				
				if(!$logDirPath) {
					throw new \Exception("Log path not defined");
				}
				
				file_put_contents(
					rtrim($logDirPath, '/\\') .'/'. date("Y-m-d") .'.log',
					"[". $datetime ."] ". $level .": ". (is_array($string)?print_r($string, true):$string) ."\n",
					FILE_APPEND
				);
			} catch(\Throwable $e) {
				// do nothing
			}
			
			try {
				if(!config('log.database.enabled', false)) {
					return;
				}
				
				$log_table = config('log.table', 'log');
				
				if(!$log_table) {
					throw new \Exception("Log table not defined");
				}
				
				if(!self::isLevelAtOrBelow($level, config('log.database.level', 'DEBUG'))) {
					throw new \Exception("Log level below database log threshold");
				}
				
				db_query("
					INSERT INTO `". esc_sql($log_table) ."`
					SET
						log.datetime = '". esc_sql($datetime) ."',
						log.level = '". esc_sql($level) ."',
						log.message = '". esc_sql( (is_array($string)?json_encode($string):$string) ) ."',
						log.backtrace = '". esc_sql(json_encode(debug_backtrace())) ."'
				");
			} catch(\Throwable $e) {
				// do nothing
			}
			
			// console
			try {
				if(!config('log.console.enabled', false)) {
					throw new \Exception("Console logging not enabled");
				}
				
				if(!self::isLevelAtOrBelow($level, config('log.console.level', 'DEBUG'))) {
					throw new \Exception("Log level below console log threshold");
				}
				
				print "[". $datetime ."] ". $level .": ". (is_array($string)?print_r($string, true):$string) ."\n";
				
				flush();
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