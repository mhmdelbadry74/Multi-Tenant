<?php

namespace App\Exceptions\Custom;

use Exception;

class JwtException extends Exception
{
    protected $statusCode;
    protected $errorCode;

    public function __construct(string $message = 'JWT authentication failed', int $statusCode = 401, string $errorCode = 'JWT_ERROR')
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
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
            'success' => false,
            'message' => $this->getMessage(),
            'error' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'timestamp' => now()->toISOString(),
        ], $this->getStatusCode());
    }
}
