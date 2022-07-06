<?php
namespace CeusMedia\Cache\Test\Adapter;

use CeusMedia\Cache\Adapter\Folder as FolderAdapter;
use CeusMedia\Cache\Test\TestCase;

class FolderTest extends TestCase
{
	public function test_Construct()
	{
		$path	= $this->pathTests.'data/tmp/adapter/';

		$object	= new FolderAdapter( $path.'folder' );
		$this->assertInstanceOf( FolderAdapter::class, $object );

		$this->assertTrue( is_dir( $path.'folder' ) );
	}
}
