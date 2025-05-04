<?php

	namespace App\Databases\Scheme;

	trait DataRowHandler
	{
		public function fetch(): array {
			if ($this->driver === 'pdo') {
				return $this->result->fetch() ?: [];
			} else {
				return $this->result->fetch_assoc() ?: [];
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
				return (int) $this->result->lastInsertId();
			} else {
				return (int) $this->object->insert_id;
			}
		}

		function col(): array {
			return [];
		}

		function field(): mixed {
			return '';
		}

		function row(): array {
			return [];
		}
	}