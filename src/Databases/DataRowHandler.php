<?php

	namespace App\Databases\Scheme;

	use PDO;

	trait DataRowHandler
	{
		public function fetch(): array {
			if ($this->driver === 'pdo') {
				return $this->result->fetchAll(PDO::FETCH_ASSOC) ?: [];
			} else {
				$rows = [];
				while ($row = mysqli_fetch_row($this->result)) {
					$rows[] = $row;
				}
				return $rows;
			}
		}

		public function count(): int {
			if ($this->driver === 'pdo') {
				return $this->result->rowCount();
			} else {
				return is_object($this->result) ? $this->result->num_rows : 0;
			}
		}

		public function lastID(): int {
			if ($this->driver === 'pdo') {
				return (int) $this->object->lastInsertId();
			} else {
				return (int) $this->object->insert_id;
			}
		}

		function col(): array {
			$array = [];
			$keyColumn = null;
			$data = $this->fetch();
			foreach ($data[0] ?? [] as $key => $value) {
				$keyColumn = $key;
				break;
			}

			foreach ($data as $row) {
				$array[] = $row[$keyColumn] ?? null;
			}
			return $array;
		}

		function field(): mixed {
			$data = $this->fetch();
			$field = null;
			foreach ($data[0] ?? [] as $value) {
				$field = $value;
				break;
			}
			return $field;
		}

		function row(): array {
			$data = $this->fetch();
			return $data[0] ?? [];
		}
	}