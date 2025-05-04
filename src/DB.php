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

		public static function configure(array $config): void
		{
			$driver      = $config['driver']      ?? 'mysql';
			$host        = $config['host']        ?? '127.0.0.1';
			$port        = $config['port']        ?? '3306';
			$database    = $config['database']    ?? '';
			$username    = $config['username']    ?? 'root';
			$password    = $config['password']    ?? '';
			$charset     = $config['charset']     ?? 'utf8mb4';
			$unixSocket  = $config['unix_socket'] ?? '';

			$dsn = $unixSocket
				? "$driver:unix_socket=$unixSocket;dbname=$database;charset=$charset"
				: "$driver:host=$host;port=$port;dbname=$database;charset=$charset";

			// Initialize PDO
			try {
				self::$pdo = new PDO($dsn, $username, $password, [
					PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES   => false,
				]);
			} catch (PDOException $e) {
				throw new RuntimeException("PDO Connection failed: " . $e->getMessage(), (int)$e->getCode());
			}

			// Initialize MySQLi
			$socket = $unixSocket ?: null;
			self::$mysqli = new mysqli($host, $username, $password, $database, (int)$port, $socket);

			if (self::$mysqli->connect_error) {
				throw new RuntimeException("MySQLi Connection failed: " . self::$mysqli->connect_error);
			}

			if (!self::$mysqli->set_charset($charset)) {
				throw new RuntimeException("Error setting charset to $charset: " . self::$mysqli->error);
			}
		}

		public static function table(string $table): Eloquent {
			return (new Eloquent)->table($table);
		}

		public static function run(string $query, array $params = []): DBResult
		{
			if (empty($params)) {
				$result = self::$mysqli->query($query);
				if ($result === false) {
					throw new RuntimeException("MySQLi Query Error: " . self::$mysqli->error);
				}
				return new DBResult($result, 'mysqli', self::$mysqli);
			}

			$stmt = self::$pdo->prepare($query);
			$stmt->execute($params);
			return new DBResult($stmt, 'pdo', self::$pdo);
		}
	}
