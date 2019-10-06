<?php
namespace Pasteur\Commands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

class StartCommand extends SystemCommand {
	protected $name = 'start';
	protected $description = 'Start command';
	protected $usage = '/start';
	protected $version = '1.0.0';

	public function execute() {
		$message = $this->getMessage();
		$chat_id = $message->getChat()->getId();

		return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => 'Hello world ! '
		]);
	}
}
