<?php

	namespace App\Databases\Handler;

	use App\Databases\Database;

	/**
	 * Class MetaData
	 *
	 * A lightweight metadata cache for single database rows,
	 * identified by table and ID. Once loaded, the row is stored
	 * in memory and can be accessed via property getters or array form.
	 *
	 * Provides automatic caching, invalidation, and lazy loading.
	 *
	 * Example usage:
	 * ```php
	 * $user = MetaData::instance(5, 'users');
	 * echo $user->name; // Accesses the "name" column
	 *
	 * $user->invalidate(); // Refreshes the cache from the database
	 * $data = $user->data(); // Get all fields as an array
	 * ```
	 *
	 * @package Databases\Handler
	 */
	final class MetaData
	{
		/**
		 * Cache of MetaData instances, indexed by table + ID.
		 *
		 * @var array<string, array<int, self>>
		 */
		private static array $instance = [];

		/**
		 * The row data as an associative array.
		 *
		 * @var array<string, mixed>
		 */
		private array $data = [];

		/**
		 * The table name (lowercased).
		 *
		 * @var string
		 */
		private string $table;

		/**
		 * The primary key ID.
		 *
		 * @var int
		 */
		private int $id;

		/**
		 * Get (or create) a cached instance for the given table and ID.
		 *
		 * @param int $id The primary key value.
		 * @param string $table The table name.
		 * @param string $primaryKey
		 * @return self
		 */
		public static function instance(int $id, string $table, string $primaryKey): self
		{
			$table = strtolower($table);
			if (!isset(self::$instance[$table][$id])) {
				self::$instance[$table][$id] = new self($id, $table, $primaryKey);
			}

			return self::$instance[$table][$id];
		}

		/**
		 * Private constructor.
		 *
		 * Fetches the row data from the database and caches it.
		 *
		 * @param int    $id    The primary key value.
		 * @param string $table The table name.
		 * @param string $primaryKey Table primary key.
		 */
		private function __construct(int $id, string $table, string $primaryKey)
		{
			$this->id = $id;
			$this->table = strtolower($table);

			$data = Database::table($this->table)
				->where($primaryKey, '=', $id)
				->limit(1)
				->row();

			foreach ($data as $key => $value) {
				$this->data[$key] = $value;
			}
		}

		/**
		 * Invalidate (refresh) the cached instance for this table + ID.
		 *
		 * @return void
		 */
		public function invalidate(): void
		{
			unset(self::$instance[$this->table][$this->id]);
			self::$instance[$this->table][$this->id] = new self($this->id, $this->table);
		}

		/**
		 * Get all metadata fields as an associative array.
		 *
		 * @return array<string, mixed>
		 */
		public function data(): array
		{
			return $this->data;
		}

		/**
		 * Magic getter for accessing individual fields.
		 *
		 * Example:
		 * ```php
		 * $user = MetaData::instance(5, 'users');
		 * echo $user->email;
		 * ```
		 *
		 * @param string $name The field/column name.
		 * @return mixed The field value, or empty string if not set.
		 */
		public function __get(string $name)
		{
			return $this->data[$name] ?? '';
		}
	}