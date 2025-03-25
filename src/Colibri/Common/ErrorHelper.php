<?php

namespace Colibri\Common;

use Colibri\App;
use Colibri\IO\Request\Encryption;
use Colibri\IO\Request\Request;
use Colibri\IO\Request\Type;

class ErrorHelper
{
    public static function Telegram(string $channel, string $message)
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
