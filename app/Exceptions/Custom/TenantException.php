<?php

namespace App\Exceptions\Custom;

use Exception;

class TenantException extends Exception
{
    protected $statusCode;
    protected $errorCode;

    public function __construct(string $message = 'Tenant operation failed', int $statusCode = 500, string $errorCode = 'TENANT_ERROR')
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
            'message' => $this->getMessage(),
            'error' => $this->getErrorCode(),
            'status_code' => $this->getStatusCode()
        ], $this->getStatusCode());
    }
}
