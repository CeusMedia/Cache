<?php
class CMM_SEA_Factory
{
	public function newStorage( $type, $resource = NULL, $data = NULL ){
		$className	= 'CMM_SEA_Adapter_'.$type;
		if( !class_exists( $className ) )
			throw new RuntimeException( 'Storage engine "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		$storage	= $reflection->newInstanceArgs( array( $resource ) );
		if( $data && is_array( $data ) )
			foreach( $data as $key => $value )
				$storate->set( $key, $value );
		return $storage;
	}

}
?>
