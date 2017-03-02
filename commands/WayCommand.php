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

class WayCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name        = 'way';
    protected $description = 'Get way point to point';
    protected $usage       = '/way';
    protected $version     = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $location = $message->getLocation();
        if ($location && $locationFrom = \BotDB::selectFromLocation($message->getFrom()->getId())) {
            $locationTo  = $location;
            $origin      = $locationFrom;
            $destination = $locationTo->getLatitude().','.$locationTo->getLongitude();

            $text = \GoogleMaps::getDirection(
                [
                    'origin' => $origin,
                    'destination' => $destination,
                    'KEY' => config::$GOOGLE_KEY,
                ]
            );

            $data = [
                'text' => $text,
                'reply_markup' => new ReplyKeyboardHide(['selective' => false])
            ];

            \BotDB::deleteFromLocation($message->getFrom()->getId());
        } elseif ($location) {
            $data = [
                'text' => 'Send your destination',
            ];

            \BotDB::updateUser($message->getFrom()->getId(), $message);
        } else {
            $data = [
                'text' => 'Send your current location',
            ];

            \BotDB::updateUser($message->getFrom()->getId(), $message);
        }

        $data ['chat_id'] = $chat_id;

        return Request::sendMessage($data);
    }
}

?>
