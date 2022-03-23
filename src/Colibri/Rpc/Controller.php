<?php

/**
 * Абстрактный класс обработчика RPC Controller
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Rpc
 * @version 1.0.0
 * 
 */

namespace Colibri\Rpc;

use Colibri\Web\Controller as WebController;

/**
 * Абстрактный класс для обработки RPC запросов
 * 
 * Наследуемся от него и создаем функцию, которую можно будет вызвать
 * например: 
 * 
 * class Controller1 extends Rpc\Controller {
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
 *          $result = peyload ответа
 *          return $this->Finish(int $code, string $message, object $result);         
 * 
 *      }
 * 
 * 
 * }
 * 
 * @testFunction testController
 */
class Controller extends WebController
{
}
