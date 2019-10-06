<?php
namespace Pasteur;

use Longman\TelegramBot\Telegram;
use React\EventLoop\LoopInterface;

class Pasteur extends Telegram {

    /**
     * @var LoopInterface
     */
    protected $loop;

    public function __construct(LoopInterface $loop) {
        parent::__construct(
            getenv('PASTEUR_API_KEY'),
            getenv('PASTEUR_USERNAME')
        );
        $this->loop = $loop;

        $this->useGetUpdatesWithoutDatabase();

        $pasteur = $this;
        Database::init($loop, function() use ($pasteur) {
            $pasteur->initTelegram();
        });
    }

    public function initTelegram(): void {
        $pasteur = $this;
        $this->loop->addPeriodicTimer(1, function() use($pasteur) {
            $pasteur->handleTelegram();
        });
    }

    public function handleTelegram(): void {
        /** @var \Longman\TelegramBot\Entities\ServerResponse Update */
        $response = $this->handleGetUpdates();
        if ($response === null || ! $response->isOk()) {
            return;
        }

        foreach($response->getResult() as $result) {
            /** @var $result \Longman\TelegramBot\Entities\Update */
            $message = $result->getEditedMessage() ?? $result->getMessage();
            if ($message !== null) {
                Database::save($message);
            }
        }
    }
}
