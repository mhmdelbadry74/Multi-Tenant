<?php

namespace App\Exceptions\Custom;

use Exception;

class TenantNotFoundException extends Exception
{
    protected $statusCode = 404;
    protected $errorCode = 'TENANT_NOT_FOUND';

    public function __construct(string $message = 'Tenant not found', int $statusCode = 404)
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
            'success' => false,
            'message' => 'Tenant not found',
            'error' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'details' => $this->getMessage(),
            'timestamp' => now()->toISOString(),
        ], $this->getStatusCode());
    }
}
