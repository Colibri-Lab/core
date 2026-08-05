<?php

/**
 * Exceptions
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Exceptions
 */

namespace Colibri\Exceptions;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;

/**
 * Exception thrown to indicate a validation error.
 * @class
 * @extends \Exception
 */
class ValidationException extends \Exception
{
    /**
     * Additional data associated with the exception.
     * @private
     * @var mixed $_exceptionData Additional data associated with the exception. This can be any type of data that provides more context about the validation error.
     */
    private mixed $_exceptionData = null;

    /**
     * Constructs a ValidationException.
     *
     * @param string $message The exception message.
     * @param int $code The exception code.
     * @param \Throwable|null $previousException The previous exception used for the exception chaining.
     * @param mixed $exceptionData Additional data associated with the exception.
     * @public
     * @constructor
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previousException = null,
        mixed $exceptionData = null
    ) {
        $this->_exceptionData = $exceptionData;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * Retrieves the additional data associated with the exception.
     *
     * @return mixed The exception data.
     * @public
     * @return mixed The additional data associated with the exception.
     */
    public function getExceptionData(): mixed
    {
        return $this->_exceptionData;
    }

    /**
     * Retrieves the additional data associated with the exception as an array.
     *
     * @return array An array containing the exception data and backtrace.
     * @public
     */
    public function getExceptionDataAsArray(): array
    {
        return VariableHelper::MixedToArray(['data' => $this->_exceptionData, 'backtrace' => debug_backtrace()]);
    }

    /**
     * Logs the exception.
     *
     * @param int $level The logging level.
     * @public
     * @return void
     */
    public function Log(int $level): void
    {
        App::$log->WriteLine($level, Debug::Rout([
            $this->getMessage(),
            $this->getCode(),
            $this->getExceptionData()
        ]));
    }

}
