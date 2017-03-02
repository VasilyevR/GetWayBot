#!/usr/bin/env
<?php
/**
 * Created by PhpStorm.
 * User: vasilyevr
 * Date: 27.02.17
 * Time: 13:14
 */

//README
//This configuration file is intented to run the bot with the webhook method
//Uncommented parameters must be filled

//bash script
//while true; do ./getUpdatesCLI.php; done

// Load composer
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/classes.php';

$commands_path = __DIR__ . '/commands';

try {
    // Create Telegram API object
    $telegram = new BotTelegram(config::$API_KEY, config::$BOT_NAME);

    // Enable MySQL
    $telegram->enableMySQL(config::$mysql);

    //// Add an additional commands path
    $telegram->addCommandsPath($commands_path);

    //// Here you can enable admin interface for the channel you want to manage
    $telegram->enableAdmins(config::$admins);

    //// Botan.io integration
    $telegram->enableBotan(config::$BOTAN_KEY);

    // Handle telegram getUpdate request
    $ServerResponse = $telegram->handleGetUpdates();

    if ($ServerResponse->isOk()) {
        $n_update = count($ServerResponse->getResult());
        if ($n_update) {
            print(date('Y-m-d H:i:s', time()) . ' - Processed ' . $n_update . ' updates' . "\n");
        }
    } else {
        print(date('Y-m-d H:i:s', time()) . ' - Failed to fetch updates' . "\n");
        echo $ServerResponse->printError() . "\n";
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e;
    // log telegram errors
    \Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    //catch log initilization errors
    echo $e;
}

?>
