<?php
/**
 * Soubor pro třídu InvalidSourceStructureException.
 * @author xhroma15
 */

namespace IPP\Student\Exceptions;

use IPP\Core\ReturnCode;
use IPP\Core\Exception\IPPException;
use Throwable;

class InvalidSourceStructureException extends IPPException
{
    public function __construct(string $message = "Semantic error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE, $previous);
    }
}
