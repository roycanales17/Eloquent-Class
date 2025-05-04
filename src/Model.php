<?php

	namespace App\Database;

	use App\Databases\Scheme\Blueprint;

	abstract class Model extends Blueprint
	{
		public static function all(): array
		{
			$object = self::object(['primary_key', 'fillable']);
			$obj = new Eloquent();
			return $obj->select('*')->table($object->getTable())->fetch();
		}

		public static function create(array $binds): int
		{
			$object = self::object(['primary_key', 'fillable']);
			$query = new Eloquent();
			return $query->create($binds, $object->getFillable(), $object->getTable())->lastID();
		}

		public static function replace(array $binds): int
		{
			$object = self::object(['primary_key', 'fillable']);
			$query = new Eloquent();
			return $query->replace($binds, $object->getFillable(), $object->getTable())->lastID();
		}

		public static function find(int $id): array
		{
			$obj = new Eloquent();
			$object = self::object(['primary_key', 'fillable']);
			$obj->select("*")->table($object->getTable())->where($object->getPrimary(), $id);
			return $obj->row();
		}

		public static function select(): Eloquent
		{
			$object = self::object(['primary_key', 'fillable']);
			$query = new Eloquent();
			foreach (func_get_args() as $column)
				$query->select($column);
			return $query->table($object->getTable());
		}

		public static function where(string $column, mixed $operator_or_value, mixed $value = self::DEFAULT_VALUE): Eloquent
		{
			$obj = new Eloquent();
			$object = self::object(['primary_key', 'fillable']);
			$obj->table($object->getTable())->where($column, $operator_or_value, $value);
			return $obj;
		}
	}
