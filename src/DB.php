<?php

	namespace App\Database;

	use App\Databases\Scheme\DBResult;
	use mysqli;
	use PDO;
	use PDOException;
	use PDOStatement;
	use RuntimeException;

	class DB
	{
		private static ?PDO $pdo = null;
		private static ?mysqli $mysqli = null;
		private static array $config = [];

		public static function configure(array $config, bool $connect = false): void
		{
			// Prevent reconfiguration with the same config
			if (self::$config === $config && self::$pdo && self::$mysqli)
				return;

			self::$config = $config;
			if ($connect) {
				self::connectPDO($config);
				self::connectMySQLi($config);
			}
		}

		private static function connectPDO(array $config): void
		{
			if(self::$pdo)
				return;

			$driver     = $config['driver'] ?? 'mysql';
			$host       = $config['host'] ?? '127.0.0.1';
			$port       = $config['port'] ?? '3306';
			$database   = $config['database'] ?? '';
			$username   = $config['username'] ?? 'root';
			$password   = $config['password'] ?? '';
			$charset    = $config['charset'] ?? 'utf8mb4';
			$unixSocket = $config['unix_socket'] ?? '';

			$dsn = $unixSocket
				? "$driver:unix_socket=$unixSocket;dbname=$database;charset=$charset"
				: "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

			try {
				self::$pdo = new PDO($dsn, $username, $password, [
					PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES   => false,
				]);
			} catch (PDOException $e) {
				throw new RuntimeException("PDO connection failed: " . $e->getMessage(), (int)$e->getCode());
			}
		}

		private static function connectMySQLi(array $config): void
		{
			if (self::$mysqli)
				return;

			$host       = $config['host'] ?? '127.0.0.1';
			$port       = (int)($config['port'] ?? 3306);
			$database   = $config['database'] ?? '';
			$username   = $config['username'] ?? 'root';
			$password   = $config['password'] ?? '';
			$charset    = $config['charset'] ?? 'utf8mb4';
			$unixSocket = $config['unix_socket'] ?? null;

			self::$mysqli = new mysqli($host, $username, $password, $database, $port, $unixSocket ?: null);

			if (self::$mysqli->connect_error) {
				throw new RuntimeException("MySQLi connection failed: " . self::$mysqli->connect_error);
			}

			if (!self::$mysqli->set_charset($charset)) {
				throw new RuntimeException("Failed to set charset to '$charset': " . self::$mysqli->error);
			}
		}

		public static function table(string $table): Eloquent
		{
			return (new Eloquent)->table($table);
		}

		/**
		 * Run a raw SQL query. If $params is empty, uses mysqli; otherwise, uses PDO.
		 * Optionally specify $driver = 'pdo' or 'mysqli' to override.
		 */
		public static function run(string $query, array $params = [], ?string $driver = null): DBResult
		{
			if (!self::$config)
				throw new RuntimeException('No configuration provided');

			self::configure(self::$config, true);

			if (!$driver)
				$driver = empty($params) ? 'mysqli' : 'pdo';

			if ($driver === 'mysqli') {

				if (!self::$mysqli)
					throw new RuntimeException("MySQLi connection not configured.");

				$result = self::$mysqli->query($query);
				if ($result === false)
					throw new RuntimeException("MySQLi query error: " . self::$mysqli->error);

				return new DBResult($result, 'mysqli', self::$mysqli);
			}

			if (!self::$pdo)
				throw new RuntimeException("PDO connection not configured.");

			$stmt = self::$pdo->prepare($query);
			$stmt->execute($params);
			return new DBResult($stmt, 'pdo', self::$pdo);
		}
	}
