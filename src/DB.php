<?php
	
	namespace Illuminate\Databases;
	
	class DB
	{
		public static function table( string $table ): Eloquent {
			return ( new Eloquent )->table( $table );
		}
		
		public static function run( string $query, array $binds ): mixed
		{
			$data = [
				'query' => $query,
				'params' => $binds
			];
			
			ob_start();
			echo "<pre style='background-color: lightblue;border-radius: 10px;padding: 1rem'>";
			highlight_string("<?php\n\n" . print_r( $data, true ) ."\n?>");
			echo '</pre>';
			$content = ob_get_contents();
			ob_end_clean();
			die( $content );
			
			return false;
		}
	}