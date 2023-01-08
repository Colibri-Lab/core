<?php

/**
 * Контейер для событий
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Events
 * @version 1.0.0
 * 
 * 
 */

namespace Colibri\Events;

/**
 * Контейер для событий
 * от этого класс должен быть наследован класс EventsContainer в Colibri\App
 * 
 * в ядре используется этот контенйер, в приложении Colibri\App\EventsContainer
 * 
 */
class EventsContainer
{

    #region Application events

    /**
     * Срабатывает после завершения инициализации приложения
     * без параметров
     */
    const AppReady = 'app.ready';

    /**
     * Начало инициализации
     * без параметров
     */
    const AppInitializing = 'app.initializing';

    #endregion

    #region Request events

    /**
     * Когда готов обьект Request
     * без параметров
     */
    const RequestReady = 'request.ready';

    #endregion

    #region Request events

    /**
     * Когда готов обьект Response
     * без параметров
     */
    const ResponseReady = 'response.ready';

    #endregion

    #region ModuleManager events 

    /**
     * Срабатывает после завершения загрузки всех модулей
     * без параметров
     */
    const ModuleManagerReady = 'modulemanager.ready';

    #endregion

    #region SecurityManager events 

    /**
     * Срабатывает после завершения загрузки всех модулей
     * без параметров
     */
    const SecurityManagerReady = 'securitymanager.ready';

    #endregion

    #region Assets

    /**
     * Начало компиляции Assets
     * параметры: string $type, string $name, string[] $blocks
     * используемая часть результата string[] $blocks
     */
    const AssetsCompiling = 'assets.compiling';

    /**
     * Компиляция assets завершена
     * параметры: string $type, string $name, string $cacheUrl
     * результат не используется
     */
    const AssetsCompiled = 'assets.compiled';

    /**
     * Компиляция блока assets завершена
     * параметры: string $type, string $name, string $content
     * используемая часть результата string $content
     */
    const AssetsBlock = 'assets.block';

    /**
     * Завершена компиляция файла в бандле
     * параметры: string $content, string $file
     * используемая часть результата string $content
     */
    const BundleFile = 'bundle.file';

    /**
     * Начата компиляция бандла
     * параметры: string[] $exts
     * используемая часть результата string $content
     */
    const BundleStart = 'bundle.start';

    /**
     * Завершена компиляция бандла
     * параметры: string $content, string[] $exts
     * используемая часть результата string $content
     */
    const BundleComplete = 'bundle.complete';

    #endregion

    #region RPC

    /**
     * Получен запрос RPC
     * параметры: string $class, string $method, stdClass $get, stdClass $post, stdClass $payload
     * результат: boolean $cancel, stdClass $result
     */
    const RpcGotRequest = 'rpc.request';

    /**
     * Запрос выполнен
     * параметры: mixed $object, string $method, stdClass $get, stdClass $post, stdClass $payload
     * результат не используется
     */
    const RpcRequestProcessed = 'rpc.complete';

    /**
     * Получен запрос RPC
     * параметры: string $class, string $method, stdClass $get, stdClass $post, stdClass $payload, string $message
     * результат: boolean $cancel, stdClass $result
     */
    const RpcRequestError = 'rpc.error';

    #endregion

    #region Template

    /**
     * Шаблон обрабатывается
     * параметры: Template $template, ExtendedObject $args
     * результат не используется
     */
    const TemplateRendering = 'template.rendering';

    /**
     * Шаблон обработан
     * параметры: Template $template, string $content
     * результат не используется
     */
    const TemplateRendered = 'template.rendered';

    #endregion

    #region Logger

    /**
     * Когда что то записано в логгер
     * параметры: int $type = Logger::*, string $message, mixed $context
     * результат не используется
     */
    const LogWriten = 'logger.writen';

#endregion

}