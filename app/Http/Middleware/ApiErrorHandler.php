<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use App\Exceptions\Custom\TenantException;
use App\Exceptions\Custom\DatabaseConnectionException;
use App\Exceptions\Custom\FileUploadException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            return $this->handleDatabaseException($e);
        } catch (ValidationException $e) {
            return $this->handleValidationException($e);
        } catch (AuthenticationException $e) {
            return $this->handleAuthenticationException($e);
        } catch (TenantException $e) {
            return $this->handleTenantException($e);
        } catch (DatabaseConnectionException $e) {
            return $this->handleDatabaseConnectionException($e);
        } catch (FileUploadException $e) {
            return $this->handleFileUploadException($e);
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }
    }

    /**
     * Handle database exceptions
     */
    private function handleDatabaseException(QueryException $e): Response
    {
        Log::error('Database error: ' . $e->getMessage());

        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

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
    private function handleValidationException(ValidationException $e): Response
    {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }

    /**
     * Handle authentication exceptions
     */
    private function handleAuthenticationException(AuthenticationException $e): Response
    {
        return response()->json([
            'message' => 'Unauthenticated',
            'error' => 'Authentication required'
        ], 401);
    }

    /**
     * Handle tenant exceptions
     */
    private function handleTenantException(TenantException $e): Response
    {
        return response()->json([
            'message' => $e->getMessage(),
            'error' => $e->getErrorCode(),
            'status_code' => $e->getStatusCode()
        ], $e->getStatusCode());
    }

    /**
     * Handle database connection exceptions
     */
    private function handleDatabaseConnectionException(DatabaseConnectionException $e): Response
    {
        return response()->json([
            'message' => 'Database connection failed',
            'error' => $e->getErrorCode(),
            'details' => app()->environment('local') ? $e->getMessage() : 'Unable to connect to database'
        ], $e->getStatusCode());
    }

    /**
     * Handle file upload exceptions
     */
    private function handleFileUploadException(FileUploadException $e): Response
    {
        return response()->json([
            'message' => 'File upload failed',
            'error' => $e->getErrorCode(),
            'details' => $e->getMessage()
        ], $e->getStatusCode());
    }

    /**
     * Handle generic exceptions
     */
    private function handleGenericException(\Exception $e): Response
    {
        Log::error('Unexpected error: ' . $e->getMessage());

        return response()->json([
            'message' => 'Internal server error',
            'error' => 'Something went wrong',
            'details' => app()->environment('local') ? $e->getMessage() : null
        ], 500);
    }
}
