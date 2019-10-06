<?php
namespace Pasteur\Commands;

use Longman\TelegramBot\Request;
use Pasteur\Database;
use Pasteur\Query;

class SpeakCommand extends BaseCommand {
	protected $name = 'speak';
	protected $description = 'Markov-2 Sentence generator';
	protected $usage = '/speak';
	protected $version = '1.0.0';

	public function execute() {
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();

		$conditions = new Query('chat_id = ?', [$chat_id]);

		Database::generate([], [$conditions], function($text) use($chat_id) {
			return static::sendSentence($text, $chat_id);
		});
		return null;
	}

}
