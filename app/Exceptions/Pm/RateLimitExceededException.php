<?php

namespace App\Exceptions\Pm;

use RuntimeException;

class RateLimitExceededException extends RuntimeException
{
    public string $action;
    public string $window;       // "hour" or "day"
    public int $limit;
    public int $retryAfterSeconds;

    public function __construct(
        string $action,
        string $window,
        int $limit,
        int $retryAfterSeconds,
        string $message = ""
    ) {
        $this->action            = $action;
        $this->window            = $window;
        $this->limit             = $limit;
        $this->retryAfterSeconds = $retryAfterSeconds;

        parent::__construct(
            $message !== ""
                ? $message
                : "PM rate limit exceeded for {$action} ({$window}: {$limit}). Retry in {$retryAfterSeconds}s."
        );
    }
}
