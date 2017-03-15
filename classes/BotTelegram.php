<?php

/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 02.03.17
 * Time: 11:18
 */

use Longman\TelegramBot\DB;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Entities;
use Longman\TelegramBot\Entities\Update;

class BotTelegram extends Longman\TelegramBot\Telegram{

    public function enableMySql(array $credential, $table_prefix = null, $encoding = 'utf8mb4')
    {
        $this->pdo = DB::initialize($credential, $this, $table_prefix, $encoding);
        BotDB::initialize($credential, $this, $table_prefix, $encoding);
        ConversationDB::initializeConversation();
        $this->mysql_enabled = true;
        return $this;
    }

    public function __construct($api_key, $bot_name)
    {
        parent::__construct($api_key, $bot_name);
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
                'location',
                'venue',
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
