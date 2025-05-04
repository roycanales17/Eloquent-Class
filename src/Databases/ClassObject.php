<?php

	namespace App\Databases\Scheme;
	
	class ClassObject
	{
		private array $fillable = [];
		private string $primary_key = "";
		private string $table;
		private string $class;
		
		function __construct( string $class )
		{
			$strings = explode( '\\', $class );
			$this->class = $class;
			$this->table = end( $strings );
		}
		
		function register( string $key, mixed $value ): void {
			$this->$key = $value;
		}
		
		function getPrimary(): string {
			return $this->primary_key ?: "id";
		}
		
		function getFillable(): array {
			return $this->fillable;
		}
		
		function getTable(): string {
			return $this->table;
		}
		
		function getClass(): string {
			return $this->class;
		}
	}