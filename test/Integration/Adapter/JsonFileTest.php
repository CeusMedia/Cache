<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\JsonFile as JsonFileAdapter;
use CeusMedia\Cache\Encoder\JSON;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\Common\FS\Folder;

class JsonFileTest extends AdapterTestCase
{
	protected string $path;
	protected string $filePath;

	public function test_construct(): void
	{
		$adapter	= new JsonFileAdapter( $this->filePath, 'context', 120 );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertFileExists( $this->filePath );
		self::assertEquals( 'context', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( ['key1'], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );
		$this->adapter->set( 'key1', 'value1' );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( ['key3', 'key4'], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setContext( NULL );
		self::assertEquals( ['key1'], $this->adapter->index() );
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

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_get(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setMultiple( ['key3' => 'value3'] );
		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );
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


/*	public function test_get_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		@unlink( $this->filePath );
		$this->adapter->get( 'test' );
	}*/

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertEquals( ['key1'], $this->adapter->index() );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( ['key1', 'key2'], $this->adapter->index() );
		self::assertEquals( ['key1' => 'value1', 'key2' => 'value2'], $this->adapter->getMultiple( ['key1', 'key2'] ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( ['key3', 'key4'], $this->adapter->index() );
		self::assertEquals( ['key3' => 'value3', 'key4' => 'value4'], $this->adapter->getMultiple( ['key3', 'key4'] ) );

		$this->adapter->setContext( 'key' );
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
		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( ['key1' => 'value1', 'key2' => 'value2'], $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( ['key1' => 'value1'], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( ['key2' => 'value2'], $this->adapter->getMultiple( ['key2'] ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( ['key3' => 'value3', 'key4' => 'value4'], $this->adapter->getMultiple( ['key3', 'key4'] ) );
		self::assertEquals( ['key3' => 'value3'], $this->adapter->getMultiple( ['key3'] ) );
		self::assertEquals( ['key4' => 'value4'], $this->adapter->getMultiple( ['key4'] ) );
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
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->set( 'key3', 'value3' );
		$this->adapter->set( 'key4', 'value4' );
		self::assertEquals( ['key3', 'key4'], $this->adapter->index() );
		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );
		self::assertEquals( 'value4', $this->adapter->get( 'key4' ) );
	}

	public function test_setByMagic(): void
	{
		parent::testSetByMagic();
	}

	public function test_setByOffset(): void
	{
		parent::testSetByOffset();
	}

	public function test_set_withException1(): void
	{
		parent::testSetWithExceptionInvalidKey();
	}

/*	public function test_set_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		@unlink( $this->filePath );
		$this->adapter->set( 'test', 'test' );
	}*/

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
		$this->filePath	= $this->path.'cache.json';
		$this->adapter	= new JsonFileAdapter( $this->filePath, NULL, 120 );
	}

	protected function tearDown(): void
	{
		@unlink( $this->filePath );
	}
}