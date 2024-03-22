<?php

/**
 * Kласс обработчика Controller
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 */

namespace Colibri\Web;

use Colibri\Common\StringHelper;
use Colibri\Utils\Cache\Mem;

/**
 * Абстрактный класс для обработки Web запросов
 *
 * Наследуемся от него и создаем функцию, которую можно будет вызвать
 * например:
 *
 * запрос:
 * /buh/web/page/method1.html
 * /buh/web/page/method1.json
 * /buh/web/page/method1.xml
 *
 * namespace App\Controllers\Buh\Web
 *
 * class PageController extends Colibri\Web\Controller {
 *
 *      public function Method1($get, $post, $payload) {
 *
 *          тут пишем что нужно и финишируем функцией Finish
 *
 *          внимание:
 *          $get, $post, $payload - изменять бессмысленно, так как это копии данных переданных в запросе
 *
 *          ЗАПРЕЩАЕТСЯ:
 *          1. выводить что либо с помощью функции echo, print_r, var_dump и т.д.
 *          2. запрашивать другие RPC Handler-ы на том же уровне
 *          3. реализовывать бизнес-логику в классе-обработчике (наследники RpcHandler)
 *
 *          $code = 200 | 400 и т.д.
 *          $message = какое либо сообщение
 *          $result = peyload ответа, может быть строкой в случае с html/xml
 *
 *          ! НИКАКОГО ECHO !!!! ЗАПРЕЩЕНО
 *
 *          пример результата:
 *
 *          div => [
 *              span => тестт
 *          ]
 *
 *          xml хелпер создаст:
 *
 *          <div><span>тестт</span></div>
 *
 *          html хелпер создаст:
 *
 *          <div class="div"><div class="span">тестт</div></div>
 *
 *
 *          return $this->Finish(int $code, string $message, mixed $result);
 *
 *      }
 *
 * }
 *
 */
class Controller
{
    protected ?string $_type = null;

    protected bool $_cache = false;
    protected int $_lifetime = 600;

    public function __construct(?string $type = null)
    {
        $this->_type = $type;
    }

    /**
     * Завершает работу обработчика
     *
     * @param int $code код ошибки
     * @param string $message сообщение
     * @param mixed $result дополнительные параметры
     * @param string $charset кодировка
     * @param array $headers дополнительные заголовки
     * @return \stdClass готовый результат
     * @testFunction testFinish
     */
    public function Finish(
        int $code,
        string $message,
        mixed $result = null,
        string $charset = 'utf-8',
        array $headers = [],
        array $cookies = [],
        bool $forceNoCache = false
    ): object {
        $res = (object) [];
        $res->code = $code;
        $res->message = $message;
        $res->result = $result;
        $res->charset = $charset;
        $res->headers = $headers;
        $res->cookies = $cookies;
        $res->forceNoCache = $forceNoCache;
        return $res;
    }

    /**
     * Создаем ссылку для добавления в url
     *
     * @param string $method название функции в классе контроллера
     * @param string $type тип возвращаемого значения: json, xml, html
     * @param array $params GET параметры
     * @return string
     * @testFunction testGetEntryPoint
     */
    public static function GetEntryPoint(string $method = '', string $type = '', array $params = []): string
    {
        $class = static::class;
        // если контроллер в модуле
        if (strpos($class, 'App\\Modules\\') === 0) {
            $class = str_replace('App\\', '', $class);
            $class = str_replace('Controllers\\', '', $class);
        } else {
            $class = str_replace('App\\Controllers\\', '', $class);
        }
        $class = str_replace('\\', '/', $class);
        $class = substr($class, 0, -1 * strlen('Controller'));
        $parts = explode('/', trim($class, '/'));
        $newParts = [];
        foreach ($parts as $c) {
            $newParts[] = StringHelper::FromCamelCaseAttr($c);
        }

        $path = implode('/', $newParts) . '/';

        if (!$method && !$type) {
            $url = StringHelper::AddToQueryString($path, $params, true);
        } elseif (!$method && $type) {
            $url = $path . 'index.' . $type;
        } else {
            $url = $path . StringHelper::FromCamelCaseAttr($method) . '.' . $type;
        }

        return '/' . StringHelper::AddToQueryString($url, $params, true);
    }

    public function __get($prop): mixed
    {
        return match($prop) {
            'cache' => $this->_cache,
            'lifetime' => $this->_lifetime
        };
    }

    public function Invoke(string $method, RequestCollection $get, RequestCollection $post, PayloadCopy $payload)
    {
        if($this->_cache) {
            $md5 = md5(App::$request->host);
            $cacheName = 'controller-' .
                str_replace('\\', '_', strtolower(static::class . '_' . $method)) . '-' .
                md5(json_encode($get->ToArray()) . json_encode($post->ToArray()) . json_encode($payload->ToArray()) . '-' .
                $md5);
            if(Mem::Exists($cacheName)) {
                $return = Mem::Read($cacheName);
            } else {
                $return = $this->$method($get, $post, $payload);
                if(!$return->forceNoCache) {
                    Mem::Write($cacheName, $return, $this->_lifetime);
                }
            }
        } else {
            $return = $this->$method($get, $post, $payload);
        }

        return $return;
    }

}
