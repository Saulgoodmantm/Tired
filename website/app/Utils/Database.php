<?php
/**
 * =============================================================================
 * TIREDOFDOINTM - Database Connection
 * =============================================================================
 * Simple PDO wrapper for PostgreSQL
 * =============================================================================
 */

namespace App\Utils;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Initialize database configuration
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Get database connection (singleton)
     */
    public static function connect(): PDO
    {
        if (self::$instance === null) {
            try {
                $driver = self::$config['connection'] ?? 'pgsql';
                
                if ($driver === 'mysql') {
                    // MySQL connection
                    $dsn = sprintf(
                        "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                        self::$config['host'],
                        self::$config['port'],
                        self::$config['name']
                    );
                    
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ];
                    
                    // Enable SSL for DigitalOcean managed MySQL
                    if (!empty(self::$config['ssl']) && self::$config['ssl'] !== 'disable') {
                        // Use constant values directly if not defined
                        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                        } else {
                            $options[1014] = false; // MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = 1014
                        }
                        if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
                        } else {
                            $options[1009] = '/etc/ssl/certs/ca-certificates.crt'; // MYSQL_ATTR_SSL_CA = 1009
                        }
                    }
                    
                    self::$instance = new PDO($dsn, self::$config['user'], self::$config['pass'], $options);
                } else {
                    // PostgreSQL connection
                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
                        self::$config['host'],
                        self::$config['port'],
                        self::$config['name'],
                        self::$config['ssl'] ?? 'require'
                    );

                    self::$instance = new PDO($dsn, self::$config['user'], self::$config['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
            } catch (PDOException $e) {
                // In production, log this instead of showing
                if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                    throw new PDOException("Database connection failed: " . $e->getMessage());
                }
                throw new PDOException("Database connection failed");
            }
        }

        return self::$instance;
    }

    /**
     * Execute a query and return all results
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return single row
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return single value
     */
    public static function queryValue(string $sql, array $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute INSERT and return last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $driver = self::$config['connection'] ?? 'pgsql';

        if ($driver === 'mysql') {
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = self::connect()->prepare($sql);
            $stmt->execute(array_values($data));
            return (int) self::connect()->lastInsertId();
        } else {
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";
            $stmt = self::connect()->prepare($sql);
            $stmt->execute(array_values($data));
            return (int) $stmt->fetchColumn();
        }
    }

    /**
     * Execute UPDATE
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $stmt = self::connect()->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));

        return $stmt->rowCount();
    }

    /**
     * Execute DELETE
     * @param string $table Table name
     * @param string|array $where WHERE clause string OR associative array for simple conditions
     * @param array $params Parameters for WHERE clause (if $where is string)
     */
    public static function delete(string $table, string|array $where, array $params = []): int
    {
        if (is_array($where)) {
            // Build WHERE from array: ['id' => 5] becomes "id = ?"
            $conditions = [];
            $params = [];
            foreach ($where as $col => $val) {
                $conditions[] = "{$col} = ?";
                $params[] = $val;
            }
            $whereStr = implode(' AND ', $conditions);
        } else {
            $whereStr = $where;
        }
        
        $sql = "DELETE FROM {$table} WHERE {$whereStr}";
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Execute raw SQL (for migrations, etc.)
     */
    public static function execute(string $sql): bool
    {
        return self::connect()->exec($sql) !== false;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::connect()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::connect()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::connect()->rollBack();
    }
}
