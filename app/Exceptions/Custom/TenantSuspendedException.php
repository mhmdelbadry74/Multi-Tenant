<?php

namespace App\Exceptions\Custom;

use Exception;

class TenantSuspendedException extends Exception
{
    protected $statusCode = 403;
    protected $errorCode = 'TENANT_SUSPENDED';

    public function __construct(string $message = 'Tenant is suspended', int $statusCode = 403)
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
            'message' => 'Tenant is suspended',
            'error' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode(),
            'details' => $this->getMessage(),
            'timestamp' => now()->toISOString(),
        ], $this->getStatusCode());
    }
}
