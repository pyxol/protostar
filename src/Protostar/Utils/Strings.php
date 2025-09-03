<?php
	namespace Protostar\Utils;
	
	/**
	 * Utility class for string operations.
	 */
	class Strings {
		/**
		 * Converts a string to camel case.
		 * Example: "hello world" becomes "helloWorld".
		 * @param string $string The input string.
		 * @return string The camel case version of the string.
		 */
		public static function camelCase(string $string): string {
			$string = strtolower($string);
			$string = preg_replace('/[^a-z0-9]+/', ' ', $string);
			$string = ucwords(trim($string));
			$string = str_replace(' ', '', $string);
			
			return lcfirst($string);
		}
		
		/**
		 * Converts a string to snake case.
		 * Example: "HelloWorld" becomes "hello_world".
		 * @param string $string The input string.
		 * @return string The snake case version of the string.
		 */
		public static function snakeCase(string $string): string {
			$string = preg_replace('/[A-Z]/', '_$0', $string);
			$string = strtolower($string);
			$string = preg_replace('/[^a-z0-9_]+/', '', $string);
			
			return trim($string, '_');
		}
		
		/**
		 * Converts a string to kebab case.
		 * Example: "HelloWorld" becomes "hello-world".
		 * @param string $string The input string.
		 * @return string The kebab case version of the string.
		 */
		public static function kebabCase(string $string): string {
			$string = preg_replace('/[A-Z]/', '-$0', $string);
			$string = strtolower($string);
			$string = preg_replace('/[^a-z0-9-]+/', '', $string);
			
			return trim($string, '-');
		}
		
		/**
		 * Converts a string to title case.
		 * Example: "hello world" becomes "Hello World".
		 * @param string $string The input string.
		 * @return string The title case version of the string.
		 */
		public static function toTitleCase(string $string): string {
			$string = strtolower($string);
			$string = preg_replace('/[^a-z0-9]+/', ' ', $string);
			$string = ucwords(trim($string));
			
			return $string;
		}
		
		
	}