<?php

	namespace App\Databases\Scheme;

	use mysqli_result;
	use PDOStatement;

	class DBResult
	{
		use DataRowHandler;

		private PDOStatement|mysqli_result|bool $result;
		private string $driver;
		private mixed $object;

		public function __construct(PDOStatement|mysqli_result|bool $result, string $driver, object $object)
		{
			$this->result = $result;
			$this->driver = $driver;
			$this->object = $object;
		}

		public function getRawResult(): PDOStatement|mysqli_result|bool
		{
			return $this->result;
		}
	}