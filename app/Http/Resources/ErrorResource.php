<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    protected $statusCode;
    protected $errorCode;
    protected $message;
    protected $details;

    public function __construct($message, $statusCode = 500, $errorCode = 'ERROR', $details = null)
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'success' => false,
            'message' => $this->message,
            'error' => $this->errorCode,
            'status_code' => $this->statusCode,
            'timestamp' => now()->toISOString(),
        ];

        // Add details in local environment or when explicitly provided
        if ($this->details && (app()->environment('local') || $this->details !== null)) {
            $response['details'] = $this->details;
        }

        // Add request ID for tracking
        if ($request->hasHeader('X-Request-ID')) {
            $response['request_id'] = $request->header('X-Request-ID');
        }

        return $response;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => config('app.version', '1.0.0'),
                'environment' => app()->environment(),
            ]
        ];
    }
}
