<?php
	
	namespace Illuminate\Databases;
	
	trait DataRow
	{
		function lastID(): int {
			return $this->execute( __FUNCTION__ );
		}
		
		function fetch(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function col(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function field(): mixed {
			return $this->execute( __FUNCTION__ );
		}
		
		function row(): array {
			return $this->execute( __FUNCTION__ );
		}
		
		function count(): int {
			return $this->execute( __FUNCTION__ );
		}
		
		function delete() {
			return $this->execute( __FUNCTION__ );
		}
		
		function update( array $binds )
		{
			$temp_col = null;
			foreach ( $binds as $key => $value ) 
			{
				$this->register_binds( $key, $value, $temp_col );
				if ( $temp_col )
					$this->update_binds[ $temp_col ] = $value;
			}
			
			return $this->execute( __FUNCTION__ );
		}
	}