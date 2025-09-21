<?php

    namespace App\Databases;

    use Closure;

    class Schema
    {
        public
        static function create(string $table, Closure $callback) {

        }

        public
        static function table(string $table, Closure $callback) {

        }

        public
        static function rename(string $from, string $to) {

        }

        public
        static function dropIfExists(string $table) {

        }

        public
        static function hasTable(string $table) {

        }

        public
        static function hasColumn(string $table, string $column) {

        }

        public
        static function hasColumns(string $table, string $columns) {

        }
    }
