<?php
namespace Pasteur;

use Longman\TelegramBot\Entities\Message;
use React\EventLoop\LoopInterface;
use React\MySQL\Factory as MySQLFactory;
use React\MySQL\QueryResult;
use React\MySQL\Io\LazyConnection;
use Exception;

class Database {

    const WORD_LENGTH = 50;

    /**
     * @var LazyConnection|null
     */
    protected static $connection = null;
    /**
     * @var MySQLFactory|null
     */
    protected static $dbfactory = null;

    public static function getConnection(): ?LazyConnection {
        if (static::$connection === null && static::$dbfactory !== null) {
            $uri = "${_ENV['DB_USER']}:${_ENV['DB_PASSWORD']}@${_ENV['DB_HOST']}/${_ENV['DATABASE']}";
            static::$connection = static::$dbfactory->createLazyConnection($uri);
        }
        return static::$connection;
    }

    public static function init(LoopInterface $loop, ?callable $then): void {
        static::$dbfactory = new MySQLFactory($loop);
        $loop->addPeriodicTimer(30, function() {
            static::getConnection()->ping();
        });
        static::getConnection()->query('SET NAMES utf8mb4');
        static::getConnection()->query('CREATE TABLE IF NOT EXISTS `words`
            (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `message_id` int(11) NOT NULL,
                `chat_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `datetime` datetime NOT NULL,
                `author_username` varchar(50) NOT NULL,
                `minus2_word` varchar(50) DEFAULT NULL,
                `minus1_word` varchar(50) DEFAULT NULL,
                `word` varchar(50) NOT NULL,
                `plus1_word` varchar(50) DEFAULT NULL,
                `plus2_word` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `message_id` (`message_id`),
                KEY `chat_id` (`chat_id`),
                KEY `user_id` (`user_id`)
            ) CHARACTER SET=utf8mb4 COLLATE utf8mb4_unicode_ci;')->then(
            function(QueryResult $command) use($then) {
                if ($then !== null) {
                    $then();
                }
            },
            function (Exception $error) use($loop) {
                $loop->addTimer(1, function() use($loop, $then) {
                    static::init($loop, $then);
                });
            }
        );
    }

    public static function save(Message $message): void {
        $entries = [];
        $placeholders = [];
        $message_id = $message->getMessageId();
        $chat_id = $message->getChat()->getId();
        $text = $message->getText();

        if (empty($text)) {
            $sticker = $message->getSticker();
            if ($sticker === null) {
                return;
            }
            $file_id = $sticker->getFileId();
            $text = "sticker:${file_id}";
        }

        if ($text[0] == "/" || $text == "-") {
            return; // Not saving sentences starting with / ou -
        }

        $sentence = explode(' ', $message->getText());
        $length = count($sentence);
        for ($i = 0 ; $i < $length; ++$i) {
            $entries = array_merge($entries, [
                $message_id,
                $chat_id,
                $message->getFrom()->getId(),
                $message->getFrom()->getFirstName(),
                ($i >= 2) ? substr($sentence[$i-2], 0, static::WORD_LENGTH) : null,
                ($i >= 1) ? substr($sentence[$i-1], 0, static::WORD_LENGTH) : null,
                substr($sentence[$i], 0, static::WORD_LENGTH),
                ($i < ($length-1)) ? substr($sentence[$i+1], 0, static::WORD_LENGTH) : null,
                ($i < ($length-2)) ? substr($sentence[$i+2], 0, static::WORD_LENGTH) : null
            ]);
            $placeholders[] = '(NULL, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)';
        }

        static::remove($chat_id, $message_id, function() use($entries, $placeholders) {
            static::getConnection()->query('INSERT INTO words VALUES '.implode(', ', $placeholders), $entries);
        });
    }

    public static function remove(int $chat_id, int $message_id, ?callable $then): void
    {
        static::getConnection()->query(
            'DELETE FROM words WHERE chat_id = ? AND message_id = ?',
            [$chat_id, $message_id],
        )->then(function(QueryResult $command) use($then) {
                if ($then !== null) {
                    $then();
                }
            }
        );
    }

    protected static function buildQuery(array $conditions): Query {
        $whereInitialized = false;
        $entries = [];
        $sql = 'SELECT minus1_word, word, plus1_word FROM words ';
        if (!empty($conditions)) {
            $sql .= 'WHERE ';
        }
        foreach($conditions as $condition) {
            if ($whereInitialized) {
                $sql .= 'AND ';
            }
            $sql .= '('.$condition->query.') ';
            $entries = array_merge($entries, $condition->entries);
            $whereInitialized = true;
        }
        $sql .= 'ORDER BY RAND() LIMIT 0, 1';

        return new Query($sql, $entries);
    }

    protected static function buildVolatileQuery(string $sentence): Query {
        $words = empty($sentence) ? [] : explode(' ', $sentence);
        $length = count($words);
        if ($length == 0) {
            return new Query('minus1_word IS NULL');
        }
        if ($length == 1) {
            return new Query('minus2_word IS NULL AND minus1_word = ?', $words);
        }

        return new Query(
            'minus2_word = ? AND minus1_word = ?',
            [$words[$length-2], $words[$length-1]]
        );
    }

    public static function generate(
        array $ordered_conditions = [],
        array $conditions = [],
        callable $then = null,
        string $sentence = ""
    ): void {
        $first_condition = null;
        if (!empty($ordered_conditions)) {
            $first_condition = array_shift($ordered_conditions);
        }

        $query_conditions = [];
        if ($first_condition !== null) {
            $query_conditions[] = $first_condition;
        }
        foreach($conditions as $condition) {
            $query_conditions[] = $condition;
        }
        $query_conditions[] = static::buildVolatileQuery(trim($sentence));
        $query = static::buildQuery($query_conditions);

        static::getConnection()->query($query->query, $query->entries)->then(
            function(QueryResult $command) use($ordered_conditions, $conditions, $then, $sentence) {
                $sentence .= ' ' . $command->resultRows[0]["word"] ?? '';

                if ($command->resultRows[0]["plus1_word"] === null) {
                    return $then(trim($sentence));
                }

                static::generate(
                    $ordered_conditions,
                    $conditions,
                    $then,
                    $sentence
                );
            }
        );
    }
}
