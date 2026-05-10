<?php

namespace App\Exceptions\Pm;

use RuntimeException;

class PmServiceException extends RuntimeException
{
    public string $reasonCode;

    public function __construct(string $reasonCode, string $message = "")
    {
        $this->reasonCode = $reasonCode;
        parent::__construct($message !== "" ? $message : "PM action denied: {$reasonCode}");
    }
}
