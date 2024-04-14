<?php
/**
 * Soubor pro třídu OperandValueException.
 * @author xhroma15
 */

namespace IPP\Student\Exceptions;

use IPP\Core\ReturnCode;
use IPP\Core\Exception\IPPException;
use Throwable;

class OperandValueException extends IPPException
{
    public function __construct(string $message = "Operand Value error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::OPERAND_VALUE_ERROR, $previous);
    }
}
