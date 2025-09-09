<?php

namespace App\Services;

use App\Exceptions\Custom\DatabaseConnectionException;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class DatabaseConnectionService
{
    /**
     * Test database connection with provided credentials
     */
    public function testConnection(array $credentials): bool
    {
        try {
            $config = config('database.connections.mysql');
            $config['database'] = $credentials['db_name'];
            $config['username'] = $credentials['db_user'];
            $config['password'] = $credentials['db_pass'];

            $connection = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5, // 5 second timeout
                ]
            );

            // Test a simple query
            $connection->query('SELECT 1');
            
            return true;

        } catch (PDOException $e) {
            Log::error('Database connection test failed: ' . $e->getMessage());
            
            $this->handlePDOException($e);
        }
    }

    /**
     * Create database if it doesn't exist
     */
    public function createDatabase(array $credentials): bool
    {
        try {
            $config = config('database.connections.mysql');
            
            // Connect without specifying database
            $connection = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // Create database
            $connection->exec("CREATE DATABASE IF NOT EXISTS `{$credentials['db_name']}`");
            
            return true;

        } catch (PDOException $e) {
            Log::error('Database creation failed: ' . $e->getMessage());
            $this->handlePDOException($e);
        }
    }

    /**
     * Create database user if it doesn't exist
     */
    public function createDatabaseUser(array $credentials): bool
    {
        try {
            $config = config('database.connections.mysql');
            
            // Connect as root/admin user
            $connection = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // Create user
            $connection->exec("CREATE USER IF NOT EXISTS '{$credentials['db_user']}'@'%' IDENTIFIED BY '{$credentials['db_pass']}'");
            
            // Grant privileges
            $connection->exec("GRANT ALL PRIVILEGES ON `{$credentials['db_name']}`.* TO '{$credentials['db_user']}'@'%'");
            $connection->exec("FLUSH PRIVILEGES");
            
            return true;

        } catch (PDOException $e) {
            Log::error('Database user creation failed: ' . $e->getMessage());
            $this->handlePDOException($e);
        }
    }

    /**
     * Handle PDO exceptions with specific error messages
     */
    private function handlePDOException(PDOException $e): void
    {
        switch ($e->getCode()) {
            case 1045: // Access denied
                throw new DatabaseConnectionException('Invalid database credentials provided');
            case 1049: // Unknown database
                throw new DatabaseConnectionException('Database does not exist');
            case 2002: // Connection refused
                throw new DatabaseConnectionException('Cannot connect to database server');
            case 2003: // Can't connect to MySQL server
                throw new DatabaseConnectionException('MySQL server is not running or not accessible');
            case 1044: // Access denied for user
                throw new DatabaseConnectionException('Insufficient privileges to perform this operation');
            case 1062: // Duplicate entry
                throw new DatabaseConnectionException('Database user already exists');
            default:
                throw new DatabaseConnectionException('Database operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get database connection status
     */
    public function getConnectionStatus(): array
    {
        try {
            $config = config('database.connections.mysql');
            
            $connection = new PDO(
                "mysql:host={$config['host']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $version = $connection->query('SELECT VERSION() as version')->fetch();
            
            return [
                'status' => 'connected',
                'version' => $version['version'],
                'host' => $config['host'],
                'port' => $config['port']
            ];

        } catch (PDOException $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }
}
