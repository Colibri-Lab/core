<?php

/**
 * Events
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Events
 */

namespace Colibri\Events;

/**
 * Container for events.
 * This class should be inherited by the EventsContainer class in Colibri\App.
 *
 * This container is used in the core, and in the application Colibri\App\EventsContainer.
 */
class EventsContainer
{
    #region Application events

    /**
     * Triggered after the application initialization is complete.
     * No parameters.
     */
    public const AppReady = 'app.ready';

    /**
     * Start of application initialization.
     * No parameters.
     */
    public const AppInitializing = 'app.initializing';

    #endregion

    #region Request events

    /**
     * When the Request object is ready.
     * No parameters.
     */
    public const RequestReady = 'request.ready';

    #endregion

    #region Request events

    /**
     * When the Response object is ready.
     * No parameters.
     */
    public const ResponseReady = 'response.ready';

    #endregion

    #region ModuleManager events

    /**
     * Triggered after all modules have been loaded.
     * No parameters.
     */
    public const ModuleManagerReady = 'modulemanager.ready';

    #endregion

    #region SecurityManager events

    /**
     * Triggered after the SecurityManager has been initialized.
     * No parameters.
     */
    public const SecurityManagerReady = 'securitymanager.ready';

    #endregion

    #region Assets

    /**
     * Start of assets compilation.
     * Parameters: string $type, string $name, string[] $blocks
     * Used part of the result: string[] $blocks
     */
    public const AssetsCompiling = 'assets.compiling';

    /**
     * Completion of assets compilation.
     * Parameters: string $type, string $name, string $cacheUrl
     * The result is not used.
     */
    public const AssetsCompiled = 'assets.compiled';

    /**
     * Completion of block assets compilation.
     * Parameters: string $type, string $name, string $content
     * Used part of the result: string $content
     */
    public const AssetsBlock = 'assets.block';

    /**
     * Completion of file compilation in the bundle.
     * Parameters: string $content, string $file
     * Used part of the result: string $content
     */
    public const BundleFile = 'bundle.file';

    /**
     * Start of bundle compilation.
     * Parameters: string[] $exts
     * Used part of the result: string $content
     */
    public const BundleStart = 'bundle.start';

    /**
     * Completion of bundle compilation.
     * Parameters: string $content, string[] $exts
     * Used part of the result: string $content
     */
    public const BundleComplete = 'bundle.complete';

    #endregion

    #region RPC

    /**
     * Received RPC request.
     * Parameters: string $class, string $method, stdClass $get, stdClass $post, stdClass $payload
     * Result: boolean $cancel, stdClass $result
     */
    public const RpcGotRequest = 'rpc.request';

    /**
     * Request processed.
     * Parameters: mixed $object, string $method, stdClass $get, stdClass $post, stdClass $payload
     * The result is not used.
     */
    public const RpcRequestProcessed = 'rpc.complete';

    /**
     * RPC request error.
     * Parameters: string $class, string $method, stdClass $get, stdClass $post, stdClass $payload, string $message
     * Result: boolean $cancel, stdClass $result
     */
    public const RpcRequestError = 'rpc.error';

    #endregion

    #region Template

    /**
     * Template is being rendered.
     * Parameters: Template $template, ExtendedObject $args
     * The result is not used.
     */
    public const TemplateRendering = 'template.rendering';

    /**
     * Template has been rendered.
     * Parameters: Template $template, string $content
     * The result is not used.
     */
    public const TemplateRendered = 'template.rendered';

    #endregion

    #region Logger

    /**
     * When something is written to the logger.
     * Parameters: int $type = Logger::*, string $message, mixed $context
     * The result is not used.
     */
    public const LogWriten = 'logger.writen';

    #endregion

    #region JobParallelProcesses

    public const ParallelJobIsEnded = 'job.parallel.ended';

    #endregion

}
