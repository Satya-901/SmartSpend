<?php
/**
 * Database Connection & Environment Loader
 */

// Function to load .env file
function load_env($filePath) {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Strip enclosing quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env first if it exists; if not, check for .env.live
if (file_exists(__DIR__ . '/../.env')) {
    load_env(__DIR__ . '/../.env');
}
if (file_exists(__DIR__ . '/../.env.live')) {
    load_env(__DIR__ . '/../.env.live');
}

// Helper to get env variable with fallback
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// Centralized PDO Database Connection
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = env('DB_HOST', 'localhost');
            $port = env('DB_PORT', '3306');
            $db   = env('DB_NAME', 'expense_tracker_db');
            $user = env('DB_USER', 'root');
            $pass = env('DB_PASS', '');
            $charset = 'utf8mb4';

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Build candidate hosts (e.g. Hostinger grants user@localhost; PDO to 127.0.0.1 can fail with 1044/2002)
            $hosts = [$host];
            if ($host === '127.0.0.1') {
                $hosts[] = 'localhost';
            } elseif ($host === 'localhost') {
                $hosts[] = '127.0.0.1';
            }

            // Build candidate database names in case of common typos (e.g. SmartSpends vs SmartSpend, casing)
            $dbCandidates = [$db];
            if (str_ends_with($db, 's')) {
                $dbCandidates[] = substr($db, 0, -1);
            } else {
                $dbCandidates[] = $db . 's';
            }
            $dbCandidates[] = strtolower($db);
            if (str_ends_with(strtolower($db), 's')) {
                $dbCandidates[] = substr(strtolower($db), 0, -1);
            }
            $dbCandidates = array_values(array_unique($dbCandidates));

            $lastException = null;

            // Step 1: Attempt connection across candidate host and database pairs
            foreach ($hosts as $h) {
                foreach ($dbCandidates as $d) {
                    try {
                        $dsn = "mysql:host={$h};port={$port};dbname={$d};charset={$charset}";
                        self::$instance = new PDO($dsn, $user, $pass, $options);
                        break 2;
                    } catch (PDOException $e) {
                        $lastException = $e;
                        // If error 1045 (invalid credentials), trying different database names won't help on this host
                        if ($e->getCode() == 1045) {
                            break;
                        }
                    }
                }
            }

            // Step 2: If still not connected and error was 1044 (access denied to db) or 0,
            // connect without dbname and probe SHOW DATABASES to discover the exact assigned database
            if (self::$instance === null && $lastException !== null && ($lastException->getCode() == 1044 || $lastException->getCode() == 0)) {
                foreach ($hosts as $h) {
                    try {
                        $dsn = "mysql:host={$h};port={$port};charset={$charset}";
                        $probe = new PDO($dsn, $user, $pass, $options);
                        $stmt = $probe->query("SHOW DATABASES");
                        $available = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $userDbs = array_values(array_filter($available, function($name) {
                            return !in_array(strtolower($name), ['information_schema', 'performance_schema', 'mysql', 'sys']);
                        }));

                        if (!empty($userDbs)) {
                            $selectedDb = $userDbs[0];
                            foreach ($userDbs as $udb) {
                                if (stripos($udb, 'spend') !== false) {
                                    $selectedDb = $udb;
                                    break;
                                }
                            }
                            $probe->exec("USE `{$selectedDb}`");
                            self::$instance = $probe;
                            break;
                        } else {
                            $lastException = new PDOException("User '{$user}' authenticated on '{$h}', but has no databases assigned in Hostinger. Available databases: " . implode(', ', $available));
                        }
                    } catch (PDOException $pe) {
                        // ignore and try next host
                    }
                }
            }

            if (self::$instance === null) {
                $errMsg = $lastException ? $lastException->getMessage() : 'Unknown database connection error';
                if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $errMsg]);
                    exit;
                }
                die("Database connection error: " . htmlspecialchars($errMsg));
            }

            // Step 3: Auto-bootstrap tables from schema.sql if empty database
            self::ensureTables(self::$instance);
        }

        return self::$instance;
    }

    private static function ensureTables(PDO $pdo): void {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt && $stmt->fetch()) {
                return; // Tables already exist
            }

            // If users table does not exist, run schema.sql
            $schemaFile = __DIR__ . '/../schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                // Remove CREATE DATABASE and USE statements
                $sql = preg_replace('/CREATE\s+DATABASE[^;]+;/i', '', $sql);
                $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
                $pdo->exec($sql);
            }
        } catch (Throwable $t) {
            error_log("Schema auto-bootstrap notice: " . $t->getMessage());
        }
    }
}

// Convenience function to get db connection
function get_db(): PDO {
    return Database::getConnection();
}

