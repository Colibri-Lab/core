<?php

namespace Colibri\Web;

/**
 * Explicit HTTP response that bypasses RPC envelopes and localization.
 * @class
 * @memberof Colibri\Web
 * @extends Object
 */
class RawResponse
{
    /**
     * HTTP response status.
     * @var int
     * @public
     */
    public int $status;
    /**
     * HTTP response headers.
     * @var array
     * @public
     */
    public array $headers;
    /**
     * Unmodified response bytes.
     * @var string
     * @public
     */
    public string $body;

    /**
     * Creates an HTTP response without application-level wrapping.
     * @param int $status HTTP status.
     * @param array $headers Header mapping.
     * @param string $body Response bytes.
     * @public
     */
    public function __construct(int $status, array $headers = [], string $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Emits this response through the ordinary PHP web SAPI.
     * @return void
     * @public
     */
    public function Send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}