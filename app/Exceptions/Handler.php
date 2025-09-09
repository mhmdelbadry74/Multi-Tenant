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
            //
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
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Authentication required'
            ], 401);
        }

        // Throttle errors
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'message' => 'Too many requests',
                'error' => 'Rate limit exceeded'
            ], 429);
        }

        // Not found errors
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested resource could not be found'
            ], 404);
        }

        // Method not allowed errors
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'message' => 'Method not allowed',
                'error' => 'The HTTP method is not allowed for this endpoint'
            ], 405);
        }

        // Access denied errors
        if ($e instanceof AccessDeniedHttpException) {
            return response()->json([
                'message' => 'Access denied',
                'error' => 'You do not have permission to access this resource'
            ], 403);
        }

        // Generic server error
        return response()->json([
            'message' => 'Internal server error',
            'error' => app()->environment('local') ? $e->getMessage() : 'Something went wrong'
        ], 500);
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
                return response()->json([
                    'message' => 'Database connection failed',
                    'error' => 'Invalid database credentials',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);

            case 1049: // Unknown database
                return response()->json([
                    'message' => 'Database not found',
                    'error' => 'The specified database does not exist',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);

            case 2002: // Connection refused
                return response()->json([
                    'message' => 'Database connection failed',
                    'error' => 'Unable to connect to database server',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);

            case 1062: // Duplicate entry
                return response()->json([
                    'message' => 'Duplicate entry',
                    'error' => 'A record with this information already exists',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 422);

            case 1452: // Foreign key constraint
                return response()->json([
                    'message' => 'Invalid reference',
                    'error' => 'Referenced record does not exist',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 422);

            case 1146: // Table doesn't exist
                return response()->json([
                    'message' => 'Database table not found',
                    'error' => 'Required database table is missing',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);

            default:
                return response()->json([
                    'message' => 'Database error',
                    'error' => 'An error occurred while processing your request',
                    'details' => app()->environment('local') ? $errorMessage : null
                ], 500);
        }
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(ValidationException $e)
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }
}
