<?php
namespace Pasteur;

use React\EventLoop\LoopInterface;
use React\MySQL\Factory as MySQLFactory;
use React\MySQL\QueryResult;
use Exception;

class Database {

    protected static $connection = null;

    public function init(LoopInterface $loop, ?callable $then): void {
        $db = new MySQLFactory($loop);
        $uri = "${_ENV['DB_USER']}:${_ENV['DB_PASSWORD']}@${_ENV['DB_HOST']}/${_ENV['DATABASE']}";

        if (static::$connection === null) {
            static::$connection = $db->createLazyConnection($uri);
        }
        static::$connection->query('CREATE TABLE IF NOT EXISTS `words`
            (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `message_id` int(11) NOT NULL,
                `chat_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `datetime` datetime NOT NULL,
                `author_username` varchar(50) NOT NULL,
                `minus2_word` varchar(500) DEFAULT NULL,
                `minus1_word` varchar(500) DEFAULT NULL,
                `word` varchar(500) NOT NULL,
                `plus1_word` varchar(500) DEFAULT NULL,
                `plus2_word` varchar(500) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `message_id` (`message_id`),
                KEY `chat_id` (`chat_id`),
                KEY `user_id` (`user_id`)
            )')->then(
            function(QueryResult $command) use($then) {
                if ($then !== null) {
                    $then();
                }
            },
            function (Exception $error) use($loop) {
                var_dump($error);
                $loop->addTimer(1, function() use($loop, $then) {
                    static::init($loop, $then);
                });
            }
        );
    }
}
