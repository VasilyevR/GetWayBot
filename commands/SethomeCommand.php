<?php
/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 27.02.17
 * Time: 14:20
 */
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

class SethomeCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name        = 'sethome';
    protected $description = 'Set home location';
    protected $usage       = '/sethome';
    protected $version     = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = 'We have a problems...';

        if ($message->getLocation()) {
            if (\BotDB::updateUser($message->getFrom()->getId(), $message)) {
                $text = 'Location saved.';
            }
        } else {
            $text = 'Send home location';
            \BotDB::updateUser($message->getFrom()->getId(), $message);
        }

        $data = [
            'text' => $text,
            'chat_id' => $chat_id,
        ];

        return Request::sendMessage($data);
    }
}

?>