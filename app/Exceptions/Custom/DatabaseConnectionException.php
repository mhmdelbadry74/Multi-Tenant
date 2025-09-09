<?php

namespace App\Exceptions\Custom;

use Exception;

class DatabaseConnectionException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode = 'DATABASE_CONNECTION_ERROR';

    public function __construct(string $message = 'Database connection failed', int $statusCode = 500)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function render($request)
    {
        return response()->json([
            'message' => 'Database connection failed',
            'error' => $this->getErrorCode(),
            'details' => app()->environment('local') ? $this->getMessage() : 'Unable to connect to database'
        ], $this->getStatusCode());
    }
}
