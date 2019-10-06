<?php
namespace Pasteur\Commands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Pasteur\Database;

class SpeakCommand extends UserCommand {
	protected $name = 'speak';
	protected $description = 'Markov-2 Sentence generator';
	protected $usage = '/speak';
	protected $version = '1.0.0';

	public function execute() {
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();

		Database::generate([], [], function($text) use($chat_id) {
			return Request::sendMessage([
				'chat_id' => $chat_id,
				'text' => $text
			]);
		});
		return null;
	}

}
