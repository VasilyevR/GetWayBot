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
use Longman\TelegramBot\Entities\ReplyKeyboardHide;
use config;

class WorkCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name        = 'work';
    protected $description = 'Get way to work';
    protected $usage       = '/work';
    protected $version     = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $destination = \BotDB::selectWorkLocation($message->getFrom()->getId());

        if ($destination && $location = $message->getLocation()) {
            $origin      = $location->getLatitude().','.$location->getLongitude();
            $destination = \BotDB::selectWorkLocation($message->getFrom()->getId());
            $text        = \GoogleMaps::getDirection(
                [
                    'origin' => $origin,
                    'destination' => $destination,
                    'KEY' => config::$GOOGLE_KEY,
                ]
            );
            $data        = [
                'text' => $text,
                'reply_markup' => new ReplyKeyboardHide(['selective' => false])
            ];
        } elseif($destination == '') {
            $data = [
                'text' => 'Set your work location - /setwork',
            ];
        } else {
            $data = [
                'text' => 'Send your current location',
            ];
        }

        $data ['chat_id'] = $chat_id;

        \BotDB::updateUser($message->getFrom()->getId(), $message);

        return Request::sendMessage($data);
    }
}

?>
