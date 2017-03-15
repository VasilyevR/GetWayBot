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
use config;

class HomeCommand extends UserCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name        = 'home';
    protected $description = 'Get way to home';
    protected $usage       = '/home';
    protected $version     = '1.0.1';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $destination = \BotDB::selectHomeLocation($message->getFrom()->getId());

        if ($destination && $location = $message->getLocation()) {
            $origin = $location->getLatitude().','.$location->getLongitude();
            $text   = \GoogleMaps::getDirection(
                [
                    'origin' => $origin,
                    'destination' => $destination,
                    'KEY' => config::$GOOGLE_KEY,
                ]
            );
            $data   = [
                'text' => $text,
            ];
        } elseif($destination == '') {
            $data = [
                'text' => 'Set your home location - /sethome',
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
