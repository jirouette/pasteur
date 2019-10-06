<?php
namespace Pasteur\Commands;

use Pasteur\Database;
use Pasteur\Query;

class StartWithCommand extends BaseCommand {
	protected $name = 'startwith';
	protected $description = 'Markov-2 Sentence generator with start conditions';
	protected $usage = '/startwith';
	protected $version = '1.0.0';

	public function execute() {
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();

		$args = explode(' ', trim($message->getText()));
		array_shift($args);

		$ordered_conditions = [];
		foreach($args as $word) {
			$ordered_conditions[] = new Query('word = ?', [$word]);
		}

		$conditions = new Query('chat_id = ?', [$chat_id]);

		Database::generate($ordered_conditions, [$conditions], function($text) use($chat_id) {
			return static::sendSentence($text, $chat_id);
		});
		return null;
	}
}
