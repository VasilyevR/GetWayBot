<?php

/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 02.03.17
 * Time: 11:12
 */

use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities;
use Longman\TelegramBot\Entities\Message;

class BotDB extends DB
{
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

?>
