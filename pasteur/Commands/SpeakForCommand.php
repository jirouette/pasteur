<?php
namespace Pasteur\Commands;

use Pasteur\Database;
use Pasteur\Query;

class SpeakForCommand extends BaseCommand {
	protected $name = 'speakfor';
	protected $description = 'Markov-2 Sentence generator from a specific username';
	protected $usage = '/startwith';
	protected $version = '1.0.0';

	public function execute() {
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();

		$args = explode(' ', trim($message->getText()));
		array_shift($args);

		if (empty($args)) {
			return;
		}

		$conditions = new Query('chat_id = ? AND author_username = ?', [$chat_id, str_replace('@', '', $args[0])]);

		Database::generate([], [$conditions], function($text) use($chat_id) {
			return static::sendSentence($text, $chat_id);
		});
		return null;
	}
}
