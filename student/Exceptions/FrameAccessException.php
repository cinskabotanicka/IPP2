<?php
/**
 * Soubor pro třídu FrameAccessException.
 * @author xhroma15
 */

namespace IPP\Student\Exceptions;

use IPP\Core\ReturnCode;
use IPP\Core\Exception\IPPException;
use Throwable;

class FrameAccessException extends IPPException
{
    public function __construct(string $message = "Frame access error", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::FRAME_ACCESS_ERROR, $previous);
    }
}
