<?php
/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 01.03.17
 * Time: 13:58
 */

use Longman\TelegramBot\DB;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Entities;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\Message;

class BotDB extends DB{
    /**
     * Select last command
     *
     * @param  integer $user_id
     *
     * @return string If the command was found
     * @throws TelegramException
     */
    public static function selectLastCommand($user_id)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT last_command from `' . TB_USER . '` WHERE `id` = :id');
            $sth->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth->execute();

            $result = $sth->fetchColumn();

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $result;
    }
    /**
     * Update users settings
     *
     * @param  integer $user_id
     * @param  Entities\Message $message
     *
     * @return bool If the insert was successful
     * @throws TelegramException
     */
    public static function updateUser($user_id, Message $message)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $type         = $message->getType();
        $last_command = self::selectLastCommand($user_id);
        $command      = false;
        $location     = '';
        $locationTxt  = null;

        if ($type === 'command') {
            $command = $message->getCommand();
        } elseif (in_array($type, [
            'Location',
            'Venue',
        ])) {
            $location    = $message->getLocation();
            $locationTxt = $location->getLatitude().','.$location->getLongitude();
        }

        try {
            $sql = 'UPDATE `' . TB_USER . '` SET ';

            if ($command) {
                $sql .= '`last_command` = :last_command ';
            } elseif ($location && in_array($last_command, array('sethome', 'setwork'))) {
                if ($last_command == 'sethome') {
                    $sql .= '`home_location` = :home_location ';
                } elseif ($last_command == 'setwork') {
                    $sql .= '`work_location` = :work_location ';
                }
            } elseif (in_array($last_command, array('way'))) {
                $sql .= '`from_location` = :from_location ';
            }

            $sql .= 'WHERE `id` = :id';
            $sth1 = self::$pdo->prepare($sql);

            $sth1->bindParam(':id', $user_id, \PDO::PARAM_INT);
            if ($command) {
                $sth1->bindParam(':last_command', $command, \PDO::PARAM_STR, 255);
            } elseif ($location && in_array($last_command, array('sethome', 'setwork', 'way'))) {
                if ($last_command == 'sethome') {
                    $sth1->bindParam(':home_location', $locationTxt, \PDO::PARAM_STR, 255);
                } elseif ($last_command == 'setwork') {
                    $sth1->bindParam(':work_location', $locationTxt, \PDO::PARAM_STR, 255);
                } elseif ($last_command == 'way') {
                    if ($location) {
                        $sth1->bindParam(':from_location', $locationTxt, \PDO::PARAM_STR, 255);
                    } else {
                        $sth1->bindParam(':from_location', $locationTxt, \PDO::PARAM_NULL);
                    }
                }
            } else {
                return false;
            }

            $status = $sth1->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $status;
    }
    /**
     * Select home location
     *
     * @param  integer $user_id
     *
     * @return string If the command was found
     * @throws TelegramException
     */
    public static function selectHomeLocation($user_id)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT home_location from `' . TB_USER . '` WHERE `id` = :id');
            $sth->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth->execute();

            $result = $sth->fetchColumn();

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $result;
    }
    /**
     * Select work location
     *
     * @param  integer $user_id
     *
     * @return string If the command was found
     * @throws TelegramException
     */
    public static function selectWorkLocation($user_id)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT work_location from `' . TB_USER . '` WHERE `id` = :id');
            $sth->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth->execute();

            $result = $sth->fetchColumn();

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $result;
    }
    /**
     * Select from location
     *
     * @param  integer $user_id
     *
     * @return string If the command was found
     * @throws TelegramException
     */
    public static function selectFromLocation($user_id)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('SELECT from_location from `' . TB_USER . '` WHERE `id` = :id');
            $sth->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth->execute();

            $result = $sth->fetchColumn();

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $result;
    }
    /**
     * Delete from location
     *
     * @param  integer $user_id
     *
     * @return string If the command was found
     * @throws TelegramException
     */
    public static function deleteFromLocation($user_id)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sth = self::$pdo->prepare('UPDATE `' . TB_USER . '` SET from_location = NULL WHERE `id` = :id');
            $sth->bindParam(':id', $user_id, \PDO::PARAM_INT);

            $result = $sth->execute();

        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $result;
    }
}

class BotTelegram extends Longman\TelegramBot\Telegram{

    public function enableMySql(array $credential, $table_prefix = null, $encoding = 'utf8mb4')
    {
        $this->pdo    = DB::initialize($credential, $this, $table_prefix, $encoding);
        $this->pdoBot = BotDB::initialize($credential, $this, $table_prefix, $encoding);
        ConversationDB::initializeConversation();
        $this->mysql_enabled = true;
        return $this;
    }

    public function __construct($api_key, $bot_name)
    {
        parent::__construct($api_key, $bot_name);
        //BotDB::initialize()
    }

    private function getCommandFromType($type)
    {
        return $this->ucfirstUnicode(str_replace('_', '', $type));
    }

    public function processUpdate(Update $update)
    {
        $this->update = $update;

        //If all else fails, it's a generic message.
        $command = 'genericmessage';

        $update_type = $this->update->getUpdateType();
        if (in_array($update_type, ['inline_query', 'chosen_inline_result', 'callback_query', 'edited_message'])) {
            $command = $this->getCommandFromType($update_type);
        } elseif ($update_type === 'message') {
            $message = $this->update->getMessage();

            //Load admin commands
            if ($this->isAdmin()) {
                $this->addCommandsPath(BASE_COMMANDS_PATH . '/AdminCommands', false);
            }

            $this->addCommandsPath(BASE_COMMANDS_PATH . '/UserCommands', false);

            $type = $message->getType();
            if ($type === 'command') {
                $command = $message->getCommand();
            } elseif (in_array($type, [
                'channel_chat_created',
                'delete_chat_photo',
                'group_chat_created',
                'left_chat_member',
                'migrate_from_chat_id',
                'migrate_to_chat_id',
                'new_chat_member',
                'new_chat_photo',
                'new_chat_title',
                'supergroup_chat_created',
            ])) {
                $command = $this->getCommandFromType($type);
            } elseif (in_array($type, [
                'Location',
                'Venue',
            ])) {
                $user_id      = $message->getFrom()->getId();
                $last_command = BotDB::selectLastCommand($user_id);
                if(in_array($last_command, ['sethome', 'setwork', 'home', 'work', 'way'])) {
                    $command = $last_command;
                } else {
                    $command = 'help';
                    $reply   = $message->getReplyToMessage();
                    while ($reply) {
                        $command = $reply->getCommand();
                        $reply   = $reply->getReplyToMessage();
                    }
                }
            }
        }
        //Make sure we have an up-to-date command list
        //This is necessary to "require" all the necessary command files!
        $this->getCommandsList();

        DB::insertRequest($this->update);

        return $this->executeCommand($command);
    }

}

class GoogleMaps {
    protected static function getResponse($parameters) {

        $url  = 'https://maps.googleapis.com/maps/api/directions/json?';
        $url .= http_build_query($parameters);

        return file_get_contents($url);
    }

    public static function getDirection($parameters) {
        $directions = \GuzzleHttp\json_decode(
            self::getResponse(
                [
                    'language' => 'ru',
                    'mode' => 'transit',
                    'origin' => $parameters['origin'],
                    'destination' => $parameters['destination'],
                    'key' => $parameters['KEY'],
                ]
            )
        );
        $direction  = $directions->routes[0]->legs[0];
        $distance   = $direction->distance->text;
        $departure  = $direction->departure_time->text;
        $arrival    = $direction->arrival_time->text;
        $duration   = $direction->duration->text;
        $waypoints  = '';
        foreach ($direction->steps as $step) {
            $step_text  = strip_tags($step->html_instructions);
            if (key_exists('transit_details', (array) $step)) {
                $step_stop  = $step->transit_details->arrival_stop->name;
                $step_text .= " до $step_stop";
            }
            $waypoints .= "\n$step_text - {$step->duration->text}";
        }

        return "Расстояние: $distance\nОтправление: $departure\nПрибытие: $arrival\nВремя в пути: $duration\n$waypoints";

    }
}

?>
