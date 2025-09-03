<?php
	namespace Protostar\Notification;
	
	use Telegram\Bot\Api;
	
	class TelegramNotification {
		/**
		 * @var \Telegram\Bot\Api|null
		 * Static instance of the Telegram API client
		 */
		public static Api|null $telegram = null;
		
		/**
		 * Get the Telegram instance (singleton)
		 * @return \Telegram\Bot\Api
		 */
		public static function getTelegramInstance(): Api {
			// Create the default Telegram API instance if it doesn't exist
			if(static::$telegram === null) {
				static::$telegram = new Api(static::getDefaultBotToken());
			}
			
			return static::$telegram;
		}
		
		/**
		 * Get the default bot token
		 * @return string|null
		 */
		public static function getDefaultBotToken(): string|null {
			if(defined('TELEGRAM_BOT_TOKEN') && TELEGRAM_BOT_TOKEN) {
				return TELEGRAM_BOT_TOKEN;
			}
			
			if(false !== ($env_var = getenv('TELEGRAM_BOT_TOKEN'))) {
				if(is_array($env_var)) {
					return $env_var[0];
				}
				
				return $env_var;
			}
			
			return null;
		}
		
		/**
		 * Get the default chat ID
		 * @return string|int|null
		 */
		public static function getDefaultChatId(): string|int|null {
			if(defined('TELEGRAM_CHAT_ID') && TELEGRAM_CHAT_ID) {
				return TELEGRAM_CHAT_ID;
			}
			
			if(false !== ($env_var = getenv('TELEGRAM_CHAT_ID'))) {
				if(is_array($env_var)) {
					return $env_var[0];
				}
				
				return $env_var;
			}
			
			return null;
		}
		
		/**
		 * Send a message to the Telegram chat
		 * @param string $message The message to send
		 * @param string|int|null $chatId The chat ID to send the message to
		 * @return void
		 * @throws \Telegram\Bot\Exceptions\TelegramSDKException
		 * @throws \Telegram\Bot\Exceptions\TelegramResponseException
		 * @throws \Telegram\Bot\Exceptions\TelegramException
		 */
		public static function sendMessage(
			string $message,
			string|int|null $chatId = null
		): void {
			static::getTelegramInstance()->sendMessage([
				'chat_id' => $chatId ?? static::getDefaultChatId(),
				'text' => $message,
			]);
		}
	}