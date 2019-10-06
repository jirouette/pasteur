<?php
namespace Pasteur\Commands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

abstract class BaseCommand extends UserCommand {

	public static function sendSentence(string $sentence, $chat_id)
	{
		$text = str_replace('@', '', $sentence);
		return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => $text
		]);
	}

}
