<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\SerialFile as SerialFileAdapter;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\CacheTest\TestCase;
use CeusMedia\Common\FS\File\Editor;
use CeusMedia\Common\FS\Folder;

class SerialFileTest extends AdapterTestCase
{
	protected string $path;
	protected string $filePath;

	public function test_construct(): void
	{
		$adapter	= new SerialFileAdapter( $this->filePath, 'context', 120 );

		Editor::save( $this->filePath, serialize( ['key1' => 'value1'] ) );
		self::assertFileExists( $this->filePath );
		self::assertEquals( 'context', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		Editor::save( $this->filePath, serialize( ['key1' => 'value1'] ) );
		self::assertEquals( ['key1'], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );

		Editor::save( $this->filePath, serialize( ['a1' => 'value1', 'b1' => 'value2'] ) );
		self::assertEquals( ['a1', 'b1'], $this->adapter->index() );
		$this->adapter->setContext( 'a' );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setContext( 'b' );
		self::assertEquals( ['1'], $this->adapter->index() );
	}

	public function test_delete(): void
	{
		parent::testDelete();
	}

	public function test_delete_byMagic(): void
	{
		parent::testDeleteByMagic();
	}

	public function test_delete_byOffset(): void
	{
		parent::testDeleteByOffset();
	}

	public function test_delete_withException1(): void
	{
		parent::testDeleteWithExceptionInvalidKey();
	}

	public function test_deleteMultiple(): void
	{
		parent::testDeleteMultiple();
	}

	public function test_get(): void
	{
		parent::testGet();
	}

	public function test_get_byMagic(): void
	{
		parent::testGetByMagic();
	}

	public function test_get_byOffset(): void
	{
		parent::testGetByOffset();
	}

	public function test_get_withDefault(): void
	{
		parent::testGetWithDefault();
	}

	public function test_get_withException1(): void
	{
		parent::testGetWithExceptionInvalidKey();
	}

	public function test_get_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		@unlink( $this->filePath );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->adapter->get( 'test' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		self::assertEquals( [], $this->adapter->index() );

		Editor::saveArray( $this->filePath, [serialize( ['key1' => 'value1'] )] );
		self::assertEquals( ['key1'], $this->adapter->index() );

		Editor::saveArray( $this->filePath, [serialize( ['key1' => 'value1', 'key2' => 'value2'] )] );
		self::assertEquals( ['key1', 'key2'], $this->adapter->index() );
		self::assertEquals( ['key1' => 'value1', 'key2' => 'value2'], $this->adapter->getMultiple( ['key1', 'key2'] ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( ['1', '2'], $this->adapter->index() );
		self::assertEquals( ['1' => 'value1', '2' => 'value2'], $this->adapter->getMultiple( ['1', '2'] ) );
	}

	public function test_has(): void
	{
		parent::testHas();
	}

	public function test_has_byMagic(): void
	{
		parent::testHasByMagic();
	}

	public function test_has_byOffset(): void
	{
		parent::testHasByOffset();
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_index(): void
	{
		Editor::saveArray( $this->filePath, [serialize( ['key1' => 'value1', 'key2' => 'value2'] )] );
		self::assertEquals( ['key1' => 'value1', 'key2' => 'value2'], $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( ['key1' => 'value1'], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( ['key2' => 'value2'], $this->adapter->getMultiple( ['key2'] ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( ['1' => 'value1', '2' => 'value2'], $this->adapter->getMultiple( ['1', '2'] ) );
		self::assertEquals( ['1' => 'value1'], $this->adapter->getMultiple( ['1'] ) );
		self::assertEquals( ['2' => 'value2'], $this->adapter->getMultiple( ['2'] ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_set(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( ['key1'], $this->adapter->index() );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key2', 'value2' );
		self::assertEquals( ['key1', 'key2'], $this->adapter->index() );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->set( '2', 'value2_updated' );
		$this->adapter->set( '3', 'value3' );
		self::assertEquals( ['1', '2', '3'], $this->adapter->index() );
		self::assertEquals( 'value2_updated', $this->adapter->get( '2' ) );
		self::assertEquals( 'value3', $this->adapter->get( '3' ) );
	}

	public function test_setByMagic(): void
	{
		parent::testSetByMagic();
	}

	public function test_setByOffset(): void
	{
		parent::testSetByOffset();
	}

	public function testSetWithException1(): void
	{
		parent::testSetWithExceptionInvalidKey();
	}


	public function test_set_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		@unlink( $this->filePath );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->adapter->set( 'test', 'test' );
	}

	public function test_setMultiple(): void
	{
		parent::testSetMultiple();
	}

/*	public function test_setEncoder(): void
	{
		parent::testSetEncoder();
	}*/

	public function test_setEncoder_withException(): void
	{
		parent::testSetEncoder_withException();
	}

	//  --  PROTECTED  --  //

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function setUp(): void
	{
		$this->path		= $this->pathTests.'data/tmp/adapter/';
		if( !file_exists( $this->path ) ){
			$folder	= new Folder( $this->path );
			$folder->create();
		}
		$this->filePath	= $this->path.'cache.serial';
		$this->adapter	= new SerialFileAdapter( $this->filePath );
	}

	protected function tearDown(): void
	{
		@unlink( $this->filePath );
	}
}