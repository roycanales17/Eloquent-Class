<?php 

    namespace Illuminate\Databases;

	class Blueprint
    {
		use DataRow;
		
		protected const DEFAULT_VALUE = 'default-null';
		
		protected int
			$duplicate = 0;
		
		protected bool
			$orderBy = false,
			$whereCondition = false,
			$selectColumn = false,
			$tableStatus = false,
			$offsetStatus = false,
			$limitStatus = false;
		
		protected array
			$binds = [],
			$update_binds = [];
		
		protected string
			$table = '',
			$query = '',
			$temp_grouped = '',
			$connectTable = '';
		
		protected function where_construct( string $type, $col, $operator_or_value, $value ): self
		{
			$this->append_table();
			if ( $col instanceof \Closure && is_null( $operator_or_value ) ) {
				return $this->create_group( $col, $type );
			}
			
			$this->translate_operator( $operator_or_value, $value );
			$this->register_binds( $col, $value, $temp_col );
			$this->create_bridge( $and, $type );
			$this->construct_query( $and, $col, $operator_or_value, $temp_col );
			return $this;
		}
		
		protected function append_table(): void
		{
			if ( $this->connectTable )
			{
				$this->query .= $this->connectTable;
				$this->connectTable = "";
			}
		}
		
		protected function construct_query( string $bridge, string $column, string $operator_or_value, string $temp_column ): void
		{
			if ( $this->temp_grouped )
				$this->temp_grouped .= " $bridge `$column` ".( !$operator_or_value ? '=' : $operator_or_value )." :$temp_column";
			
			$this->query .= " $bridge `$column` ".( !$operator_or_value ? '=' : $operator_or_value )." :$temp_column";
		}
		
		protected function create_bridge( &$bridge, string $type ): void
		{
			if ( !$this->whereCondition ) {
				$bridge = "WHERE";
			} else {
				$bridge = $type;
			}
			
			$this->whereCondition = true;
		}
		
		protected function register_binds( string $column, mixed $value, &$temp_col = null ): void
		{
			$temp_col = $column;
			$temp_col = explode( '.', $temp_col );
			$temp_col = $temp_col[ count( $temp_col ) - 1 ];
			if ( isset( $this->binds[ $temp_col ] ) )
			{
				$this->duplicate++;
				$temp_col = "$temp_col{$this->duplicate}";
			}
			
			$this->binds[ $temp_col ] = $value;
		}
		
		protected function translate_operator( string &$operator, string &$value ): void
		{
			if ( $value === self::DEFAULT_VALUE ) {
				$value = $operator;
				$operator = '=';
			}
		}
		
		protected function create_group( \Closure $callback, string $concat ): self
		{
			if ( !$this->whereCondition )
			{
				$concat = "";
				$this->query .= " WHERE ";
				$this->whereCondition = true;
			}
			
			if ( $this->whereCondition )
			{
				$this->query .= " $concat (";
				$this->temp_grouped .= " (";
			}
			
			$callback( $this );
			
			if ( $this->whereCondition )
			{
				$this->query .= " ) ";
				$this->temp_grouped .= " ) ";
			}
			
			return $this;
		}
		
		protected function build_query( string $action ): string
		{
			switch ( $action )
			{
				case 'update':
					$sql = "";
					$keys = array_keys( $this->update_binds );
					for ( $i = 0; $i < count( $keys ); $i++ ) {
						$key = $keys[ $i ];
						$sql .= ( !$i ? "" : ", " )."`$key` = :$key";
					}
					
					$this->query = str_replace( "FROM `{$this->table}` ", "UPDATE `{$this->table}` SET $sql ", $this->query );
					break;
				
				case 'delete':
					$this->query = str_replace( "FROM `{$this->table}` ", "DELETE FROM `{$this->table}` ", $this->query );
					break;
				
				default:
					if ( !$this->selectColumn )
						$this->query = "SELECT *{$this->query}";
					break;
			}
			
			$check = false;
			$sql = explode( ' ', preg_replace('/\s+/', ' ', trim( $this->query ) ) );
			for ( $i = 0; $i < count( $sql ); $i++ )
			{
				if ( $check )
				{
					if ( in_array( strtoupper( $sql[ $i ] ), [ 'OR', 'AND' ] ) ) {
						unset( $sql[ $i ] );
					}
					
					$check = false;
				}
				
				if ( isset( $sql[ $i ] ) && $sql[ $i ] == '(' )
					$check = true;
			}
			
			return implode( ' ', array_values( $sql ) );
		}
		
		private function execute( string $action ): mixed
		{
			$result = true;
			$sql = $this->build_query( $action );
			switch ( $action )
			{
				case 'update':
				case 'delete':
					db::run( $sql, $this->binds );
					break;
				
				default:
					$result = db::run( $sql, $this->binds )->$action();
					break;
			}
			
			return $result;
		}
		
		protected static function object( array $required = [] ): ClassObject
		{
			$name = get_called_class();
			$obj = new ClassObject( $name );
			$reflectionClass = new \ReflectionClass($name);
			
			foreach ( $required as $property )
			{
				if ( $reflectionClass->hasProperty( $property ) )
				{
					$reflectionProperty = $reflectionClass->getProperty( $property );
					$reflectionProperty->setAccessible( true );
					$value = $reflectionProperty->getValue( ( new $name ) );
					$obj->register( $property, $value );
				}
			}
			
			return $obj;
		}
		
		protected static function remove_unfillable( array &$array, array $fillable ): void
		{
			if ( !$fillable )
				return;
			
			foreach ( $array as $key => $value )
			{
				if ( !in_array( $key, $fillable ) ) {
					unset( $array[ $key ] );
				}
			}
		}
    }