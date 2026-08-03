<?php

namespace Colibri\Common;

use Colibri\App;
use Colibri\IO\Request\Encryption;
use Colibri\IO\Request\Request;
use Colibri\IO\Request\Type;

/**
 * ErrorHelper class provides utility methods for error handling and reporting.
 * @class
 */
class ErrorHelper
{
    /**
     * Sends an error message to a specified Telegram channel.
     *
     * @param string $channel The Telegram channel ID.
     * @param string $message The error message to send.
     * @return void
     * @public
     * @static
     */
    public static function Telegram(string $channel, string $message): void
    {
        $botToken = App::$config->Query('errors.telegram', '')->GetValue();
        if(!$botToken) {
            return;
        }

        $request = new Request("https://api.telegram.org/bot" . $botToken . "/sendMessage", Type::Post, Encryption::JsonEncoded);
        $response = $request->Execute([
            'chat_id' => $channel,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
        if($response->status != 200) {
            App::$log->emergency($message);
            // throw new \Exception($response->data);
        }
    }

}
