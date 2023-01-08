<?php

namespace Colibri\Exceptions;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\Utils\Debug;

class ValidationException extends \Exception
{

    private mixed $_exceptionData = null;

    public function __construct(string $message = "", int $code = 0, \Throwable $previousException = null, mixed $exceptionData = null)
    {
        $this->_exceptionData = $exceptionData;
        parent::__construct($message, $code, $previousException);
    }

    public function getExceptionData(): mixed
    {
        return $this->_exceptionData;
    }

    public function getExceptionDataAsArray(): array
    {
        return VariableHelper::MixedToArray(['data' => $this->_exceptionData, 'backtrace' => debug_backtrace()]);
    }

    public function Log(int $level): void
    {
        App::$log->WriteLine($level, Debug::Rout([
            $this->getMessage(),
            $this->getCode(),
            $this->getExceptionData()
        ]));
    }

}