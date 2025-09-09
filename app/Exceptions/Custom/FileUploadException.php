<?php

namespace App\Exceptions\Custom;

use Exception;

class FileUploadException extends Exception
{
    protected $statusCode = 422;
    protected $errorCode = 'FILE_UPLOAD_ERROR';

    public function __construct(string $message = 'File upload failed', int $statusCode = 422)
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
            'message' => 'File upload failed',
            'error' => $this->getErrorCode(),
            'details' => $this->getMessage()
        ], $this->getStatusCode());
    }
}
