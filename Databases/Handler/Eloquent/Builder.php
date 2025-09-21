<?php

    namespace App\Databases\Handler\Eloquent;

    use App\Databases\Database;
    use App\Databases\Handler\Blueprints\QueryReturnType;

    abstract class Builder extends QueryResults
    {
        const EMPTY = '__empty__';

        protected string $server;
        protected string $table = '';
        protected array $columns = [];
        protected array $wheres = [];
        protected array $bindings = [];
        protected array $orders = [];
        protected ?int $limit = null;
        protected ?int $offset = null;
        protected string $lastSql = '';
        protected string $query = '';

        protected function buildWhere(): string {
            if (empty($this->wheres)) {
                return '';
            }

            $sql = 'WHERE ';
            $parts = [];

            foreach ($this->wheres as $where) {
                if (($where[0] ?? '') === 'nested') {
                    $nestedParts = [];
                    $wheres = $where[1] ?? [];
                    $boolean = $where[2] ?? '';
                    foreach ($wheres as [$nExpr, $nType]) {
                        $nestedParts[] = ($nestedParts ? $nType . ' ' : '') . $nExpr;
                    }
                    $expr = '(' . implode(' ', $nestedParts) . ')';
                    $parts[] = ($parts ? $boolean . ' ' : '') . $expr;
                } else {
                    [$expr, $boolean] = $where;
                    $parts[] = ($parts ? $boolean . ' ' : '') . $expr;
                }
            }

            return $sql . implode(' ', $parts);
        }

        public function delete(): mixed
        {
            $sql = "DELETE FROM {$this->table} " . $this->buildWhere();
            return Database::server($this->server)->query($this->lastSql = $sql, $this->bindings)->totalAffected();
        }

        public function replace(array $data, array $fillable = [], QueryReturnType $returnType = QueryReturnType::ROW_COUNT): mixed
        {
            if (!empty($fillable)) {
                $data = array_intersect_key($data, array_flip($fillable));
            }

            $columns = array_keys($data);
            $placeholders = implode(',', array_map(fn($c) => ":$c", $columns));
            $sql = "REPLACE INTO {$this->table} (" . implode(',', $columns) . ") VALUES ($placeholders)";

            $bindings = [];
            foreach ($data as $key => $value) {
                $bindings[":$key"] = $value;
            }

            if ($returnType == QueryReturnType::ROW_COUNT) {
                return Database::server($this->server)->query($this->lastSql = $sql, $bindings)->totalAffected();
            }

            return Database::server($this->server)->query($this->lastSql, $bindings)->lastInsertedID();
        }

        public function create(array $data, array $fillable = []): mixed
        {
            if (!empty($fillable)) {
                $data = array_intersect_key($data, array_flip($fillable));
            }

            $columns = array_keys($data);
            $placeholders = implode(',', array_map(fn($c) => ":$c", $columns));
            $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES ($placeholders)";

            $bindings = [];
            foreach ($data as $key => $value) {
                $bindings[":$key"] = $value;
            }

            return Database::server($this->server)->query($this->lastSql = $sql, $bindings)->lastInsertedID();
        }

        public function rawSQL(bool $interpolate = true): string {
            if (empty($this->lastSql)) {
                $cols = $this->columns ?: ['*'];
                $sql = "SELECT " . implode(', ', $cols) . " FROM {$this->table} " . $this->buildWhere();
            } else {
                $sql = $this->lastSql;
            }

            $raw = $sql;
            if ($interpolate) {
                foreach ($this->bindings as $param => $value) {
                    $quoted = is_numeric($value) ? $value : "'" . addslashes($value) . "'";
                    if (is_string($param)) {
                        $raw = str_replace($param, $quoted, $raw);
                    } else {
                        $raw = preg_replace('/\?/', $quoted, $raw, 1);
                    }
                }
            }

            return $raw;
        }
    }
