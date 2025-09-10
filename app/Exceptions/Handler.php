<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Exceptions\Custom\JwtException;
use App\Exceptions\Custom\TenantException;
use App\Exceptions\Custom\TenantNotFoundException;
use App\Exceptions\Custom\TenantSuspendedException;
use App\Exceptions\Custom\DatabaseConnectionException;
use App\Exceptions\Custom\FileUploadException;
use App\Exceptions\Custom\ProvisioningException;
use App\Http\Resources\ErrorResource;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions except validation and authentication
            if (!($e instanceof ValidationException) && !($e instanceof AuthenticationException)) {
                Log::error('Exception occurred', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException($request, Throwable $e)
    {
        // Custom exceptions first
        if ($e instanceof JwtException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof TenantException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof TenantNotFoundException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof TenantSuspendedException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof DatabaseConnectionException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof FileUploadException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        if ($e instanceof ProvisioningException) {
            return new ErrorResource($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        // Database connection errors
        if ($e instanceof QueryException) {
            return $this->handleDatabaseException($e);
        }

        // Validation errors
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        // Authentication errors
        if ($e instanceof AuthenticationException) {
            return new ErrorResource('Unauthenticated', 401, 'AUTHENTICATION_REQUIRED');
        }

        // Throttle errors
        if ($e instanceof ThrottleRequestsException) {
            return new ErrorResource('Too many requests', 429, 'RATE_LIMIT_EXCEEDED');
        }

        // Not found errors
        if ($e instanceof NotFoundHttpException) {
            return new ErrorResource('Resource not found', 404, 'RESOURCE_NOT_FOUND');
        }

        // Method not allowed errors
        if ($e instanceof MethodNotAllowedHttpException) {
            return new ErrorResource('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
        }

        // Access denied errors
        if ($e instanceof AccessDeniedHttpException) {
            return new ErrorResource('Access denied', 403, 'ACCESS_DENIED');
        }

        // Generic server error
        return new ErrorResource(
            'Internal server error', 
            500, 
            'INTERNAL_SERVER_ERROR',
            app()->environment('local') ? $e->getMessage() : null
        );
    }

    /**
     * Handle database exceptions
     */
    protected function handleDatabaseException(QueryException $e)
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        // MySQL specific error codes
        switch ($errorCode) {
            case 1045: // Access denied
                return new ErrorResource(
                    'Database connection failed',
                    500,
                    'DATABASE_ACCESS_DENIED',
                    app()->environment('local') ? $errorMessage : null
                );

            case 1049: // Unknown database
                return new ErrorResource(
                    'Database not found',
                    500,
                    'DATABASE_NOT_FOUND',
                    app()->environment('local') ? $errorMessage : null
                );

            case 2002: // Connection refused
                return new ErrorResource(
                    'Database connection failed',
                    500,
                    'DATABASE_CONNECTION_REFUSED',
                    app()->environment('local') ? $errorMessage : null
                );

            case 1062: // Duplicate entry
                return new ErrorResource(
                    'Duplicate entry',
                    422,
                    'DUPLICATE_ENTRY',
                    app()->environment('local') ? $errorMessage : null
                );

            case 1452: // Foreign key constraint
                return new ErrorResource(
                    'Invalid reference',
                    422,
                    'FOREIGN_KEY_CONSTRAINT',
                    app()->environment('local') ? $errorMessage : null
                );

            case 1146: // Table doesn't exist
                return new ErrorResource(
                    'Database table not found',
                    500,
                    'TABLE_NOT_FOUND',
                    app()->environment('local') ? $errorMessage : null
                );

            default:
                return new ErrorResource(
                    'Database error',
                    500,
                    'DATABASE_ERROR',
                    app()->environment('local') ? $errorMessage : null
                );
        }
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(ValidationException $e)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'error' => 'VALIDATION_ERROR',
            'status_code' => 422,
            'errors' => $e->errors(),
            'timestamp' => now()->toISOString(),
        ], 422);
    }
}
