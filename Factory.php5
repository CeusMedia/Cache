<?php
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
/**
 *	....
 *	@category		cmModules
 *	@package		SEA
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@since			30.05.2011
 *	@version		$Id$
 */
class CMM_SEA_Factory{

	/**
	 *	Creates and returns new cache storage engine.
	 *	@access		public
	 *	@param		string		$type		Storage type
	 *	@param		string		$resource	Resource for storage engine
	 *	@param		array		$data		Data to store immediately
	 *	@return		CMM_SEA_Adapter_Abstract
	 */
	public function newStorage( $type, $resource = NULL, $data = NULL ){
		$className	= 'CMM_SEA_Adapter_'.$type;
		if( !class_exists( $className ) )
			throw new RuntimeException( 'Storage engine "'.$type.'" not registered' );
		$reflection	= new ReflectionClass( $className );
		if( $resource )
			$storage	= $reflection->newInstanceArgs( array( $resource ) );
		else
			$storage	= $reflection->newInstance();
		if( $data && is_array( $data ) )
			foreach( $data as $key => $value )
				$storate->set( $key, $value );
		return $storage;
	}

}
?>
