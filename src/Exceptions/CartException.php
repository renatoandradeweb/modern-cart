<?php
// src/Exceptions/CartException.php
namespace ModernCart\Exceptions;

use Exception;

class CartException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}