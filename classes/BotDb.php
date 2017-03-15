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
     * Update last command
     *
     * @param  integer $user_id
     * @param  string $command
     *
     * @return bool If the command was saved
     * @throws TelegramException
     */
    public static function updateLastCommand($user_id, $command)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql  = 'UPDATE `' . TB_USER . '` SET ';
            $sql .= '`last_command` = :last_command ';
            $sql .= 'WHERE `id` = :id';
            $sth1 = self::$pdo->prepare($sql);
            $sth1->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth1->bindParam(':last_command', $command, \PDO::PARAM_STR, 255);
            $status = $sth1->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $status;

    }
    /**
     * Update user location
     *
     * @param  integer $user_id
     * @param  string $type_location
     * @param  string $location
     *
     * @return bool If the location was saved
     * @throws TelegramException
     */
    public static function updateLocationUser($user_id, $type_location, $location)
    {
        if (!self::isDbConnected()) {
            return false;
        }

        try {
            $sql  = 'UPDATE `' . TB_USER . '` SET ';
            $sql .= "`$type_location` = :$type_location ";
            $sql .= 'WHERE `id` = :id';
            $sth1 = self::$pdo->prepare($sql);
            $sth1->bindParam(':id', $user_id, \PDO::PARAM_INT);
            $sth1->bindParam(":$type_location", $location, \PDO::PARAM_STR, 255);

            $status = $sth1->execute();
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }

        return $status;

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

        $type          = $message->getType();
        $last_command  = self::selectLastCommand($user_id);
        $command       = false;
        $location      = '';
        $locationTxt   = null;
        $locationTypes = [
            'sethome' => 'home_location',
            'setwork' => 'work_location',
            'way' => 'from_location',
        ];

        if ($type === 'command') {
            $command = $message->getCommand();
        } elseif (in_array($type, ['location','venue'])) {
            $location    = $message->getLocation();
            $locationTxt = $location->getLatitude() . ',' . $location->getLongitude();
        }

        if ($command) {
            $status = self::updateLastCommand($user_id, $command);
        } elseif ($location && in_array($last_command, array_keys($locationTypes))) {
            $status = self::updateLocationUser($user_id, $locationTypes[$last_command], $locationTxt);
        } else {
            $status = false;
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
