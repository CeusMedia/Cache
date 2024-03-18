<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\Folder as FolderAdapter;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\CacheTest\TestCase;

class FolderTest extends TestCase
{
	protected string $path;
	protected FolderAdapter $adapter;

	public function test_construct(): void
	{
		$adapter = new FolderAdapter( $this->path, 'context', 120 );
		self::assertEquals( 'context/', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );

		$this->adapter->setContext( 'a' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_delete(): void
	{
		$this->adapter->set( 'key1', 'value1' );
		self::assertTrue( $this->adapter->delete( 'key1' ) );
		self::assertFalse( $this->adapter->has( 'key1' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( 'key1' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->set( 'key2', 'value2' );
		self::assertTrue( $this->adapter->delete( 'key2' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( 'key2' ) );
	}

	public function test_delete_withException1(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->delete( '__äöü__' );
	}

/*	public function test_delete_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		$this->adapter->delete( 'not_existing' );
	}*/

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_deleteMultiple(): void
	{
		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key1', 'key2'] ) );
		self::assertEquals( [], $this->adapter->index() );

		$this->adapter->setContext( 'key' );

		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4', 'key5' => 'value5'] );
		self::assertTrue( $this->adapter->deleteMultiple( ['key3', 'key4'] ) );
		self::assertEquals( ['key5'], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_get(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->setMultiple( ['key1' => 'value1', 'key2' => 'value2'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( ['key3' => 'value3', 'key4' => 'value4'] );
		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );
		self::assertEquals( 'value4', $this->adapter->get( 'key4' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );
	}

	public function test_get_withException1(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->get( '__äöü__' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_getMultiple(): void
	{
		self::assertEquals( [], $this->adapter->index() );

		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );

		self::assertTrue( $this->adapter->setMultiple( $data1 ) );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );
		self::assertEquals( $data1, $this->adapter->getMultiple( array_keys( $data1 ) ) );

		$this->adapter->setContext( 'key' );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setMultiple( $data2 );

		self::assertTrue( $this->adapter->setMultiple( $data2 ) );
		self::assertEqualsCanonicalizing( array_keys( $data2 ), $this->adapter->index() );
		self::assertEquals( $data2, $this->adapter->getMultiple( array_keys( $data2 ) ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_index(): void
	{
		$data1	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data1 );
		self::assertEqualsCanonicalizing( array_keys( $data1 ), $this->adapter->index() );

		$this->adapter->setContext( 'key' );

		$data2	= ['key3' => 'value3', 'key4' => 'value4'];
		$this->adapter->setMultiple( $data2 );
		self::assertEqualsCanonicalizing( array_keys( $data2 ), $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_set(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key1', 'value1' );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		$this->adapter->set( 'key2', 'value2' );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );
		self::assertEqualsCanonicalizing( ['key1', 'key2'], $this->adapter->index() );

		$this->adapter->setContext( 'key' );

		$this->adapter->set( 'key3', 'value3' );

		self::assertEquals( 'value3', $this->adapter->get( 'key3' ) );
		self::assertNull( $this->adapter->get( 'key2' ) );
		self::assertEquals( ['key3'], $this->adapter->index() );
	}

	public function test_set_withException(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->set( '__äöü__', 'nothing' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_setMultiple(): void
	{
		$data	= ['key1' => 'value1', 'key2' => 'value2'];
		self::assertTrue( $this->adapter->setMultiple( $data ) );
		self::assertEquals( $data, $this->adapter->getMultiple( array_keys( $data ) ) );

		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( $data );
		self::assertEquals( $data, $this->adapter->getMultiple( array_keys( $data ) ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function setUp(): void
	{
		$this->path		= $this->pathTests.'data/tmp/adapter/folder/';
		$this->adapter	= new FolderAdapter( $this->path );
//		self::assertInstanceOf( FolderAdapter::class, $object );
//		self::assertTrue( is_dir( $path.'folder' ) );
	}

	protected function tearDown(): void
	{
		$this->adapter->clear();
		$this->adapter->setContext();
		$this->adapter->clear();
	}
}