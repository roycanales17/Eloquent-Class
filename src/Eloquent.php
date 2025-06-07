<?php

	namespace App\Database;

	use App\Databases\Scheme\Blueprint;

	class Eloquent extends Blueprint
	{
		function table(string $table): self
		{
			$table = strtolower($table);

			if (!$this->tableStatus)
				$this->query .= " FROM `$table`";
			else
				$this->query .= ", `$table`";

			if (!$this->selectColumn) {
				$this->connectTable = $this->query;
				$this->query = '';
			}

			$this->table = $table;
			$this->tableStatus = true;
			return $this;
		}

		function select(string $column): self
		{
			$col = preg_split("/\s+(?:as|AS)\s+/", $column);
			$name = trim($col[0]);
			$alias = trim($col[1] ?? false);
			$column = $name . ($alias ? " AS $alias" : "");

			if ($this->whereCondition) {
				if (!$this->selectColumn) {
					$this->query = "SELECT $column " . $this->query;
				} else {
					$pos = strpos($this->query, "FROM");
					if ($pos !== false) {
						$substring = trim(substr($this->query, 0, $pos));
						$substring .= ", $column";
						$this->query = $substring . " " . substr($this->query, $pos);
					}
				}
			} else {
				$this->query .= (!$this->selectColumn ? "SELECT $column" : ", $column");
			}

			$this->selectColumn = true;
			return $this;
		}

		function where(string|\Closure $col, mixed $operator_or_value = null, mixed $value = self::DEFAULT_VALUE): self {
			return $this->where_construct("AND", $col, $operator_or_value, $value);
		}

		function orWhere(string|\Closure $col, mixed $operator_or_value = '', mixed $value = self::DEFAULT_VALUE): self {
			return $this->where_construct("OR", $col, $operator_or_value, $value);
		}

		function orderBy(string $column, string $sort): self
		{
			if (!$this->orderBy) {
				$this->query .= " ORDER BY $column " . strtoupper($sort);
			} else {
				$this->query .= ", $column " . strtoupper($sort);
			}

			if ($this->orderBy === false) {
				$this->orderBy = true;
			}
			return $this;
		}

		function offset(int $offset): self
		{
			$this->query .= " OFFSET $offset";
			$this->offsetStatus = true;
			return $this;
		}

		function limit(int $limit): self
		{
			$this->query .= " LIMIT $limit";
			$this->limitStatus = true;
			return $this;
		}

		function create(array $binds, array $fillable = [], string $table = '')
		{
			if ($fillable) self::remove_unfillable($binds, $fillable);
			if (empty($table))
				$table = $this->table;

			$table = strtolower($table);
			$columns = array_keys($binds);
			return db::run(
				"INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (:" . implode(', :', $columns) . ")",
				$binds
			)->lastID();
		}

		function replace(array $binds, array $fillable = [], string $table = '')
		{
			if ($fillable) self::remove_unfillable($binds, $fillable);
			if (empty($table)) $table = strtolower($this->table);

			$columns = array_keys($binds);
			return db::run(
				"REPLACE INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (:" . implode(', :', $columns) . ")",
				$binds
			)->lastID();
		}
	}
