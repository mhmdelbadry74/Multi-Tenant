<?php

namespace App\Exceptions\Custom;

use Exception;

class ProvisioningException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode = 'PROVISIONING_ERROR';

    public function __construct(string $message = 'Tenant provisioning failed', int $statusCode = 500)
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
            'message' => 'Tenant provisioning failed',
            'error' => $this->getErrorCode(),
            'details' => $this->getMessage()
        ], $this->getStatusCode());
    }
}
