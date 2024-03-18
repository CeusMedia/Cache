<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace CeusMedia\CacheTest\Integration\Adapter;

use CeusMedia\Cache\Adapter\IniFile as IniFileAdapter;
use CeusMedia\Cache\SimpleCacheException;
use CeusMedia\Cache\SimpleCacheInvalidArgumentException;
use CeusMedia\CacheTest\TestCase;
use CeusMedia\Common\FS\File\Editor;
use CeusMedia\Common\FS\Folder;

class IniFileTest extends TestCase
{
	protected string $path;
	protected string $filePath;
	protected IniFileAdapter $adapter;

	public function test_construct(): void
	{
		$adapter	= new IniFileAdapter( $this->filePath, 'context', 120 );

		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		self::assertFileExists( $this->filePath );
		self::assertEquals( 'context', $adapter->getContext() );
		self::assertEquals( 120, $adapter->getExpiration() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_clear(): void
	{
		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		self::assertEquals( ['key1'], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->clear() );

		Editor::saveArray( $this->filePath, ['a1 = "value1"', 'b1 = "value2"'] );
		self::assertEquals( ['a1', 'b1'], $this->adapter->index() );
		$this->adapter->setContext( 'a' );
		self::assertTrue( $this->adapter->clear() );
		self::assertEquals( [], $this->adapter->index() );
		$this->adapter->setContext( 'b' );
		self::assertEquals( ['1'], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_delete(): void
	{
		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertTrue( $this->adapter->has( 'key1' ) );
		self::assertEquals( ['key1'], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( 'key1' ) );
		self::assertFalse( $this->adapter->has( 'key1' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( 'key1' ) );

		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		$this->adapter->setContext( 'key' );
		self::assertEquals( ['1'], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( '1' ) );
		self::assertFalse( $this->adapter->has( '1' ) );
		self::assertEquals( [], $this->adapter->index() );
		self::assertTrue( $this->adapter->delete( '1' ) );
	}

	public function test_delete_withException1(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->delete( '__äöü__' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_deleteMultiple(): void
	{
		Editor::saveArray( $this->filePath, [
			'key1 = "value1"',
			'key2 = "value2"',
			'key3 = "value3"',
			'key4 = "value4"',
			'key5 = "value5"',
			'key6 = "value6"',
		] );
		self::assertEquals( ['key1', 'key2', 'key3', 'key4', 'key5', 'key6'], $this->adapter->index() );
		self::assertTrue( $this->adapter->deleteMultiple( ['key2', 'key3'] ) );
		self::assertEquals( ['key1', 'key4', 'key5', 'key6'], $this->adapter->index() );

		self::assertTrue( $this->adapter->deleteMultiple( ['key2', 'key3'] ) );
		self::assertEquals( ['key1', 'key4', 'key5', 'key6'], $this->adapter->index() );

		self::assertTrue( $this->adapter->deleteMultiple( ['key1', 'key4'] ) );
		self::assertEquals( ['key5', 'key6'], $this->adapter->index() );

		self::assertTrue( $this->adapter->deleteMultiple( ['key6'] ) );
		self::assertEquals( ['key5'], $this->adapter->index() );

		$this->adapter->setContext( 'key' );
		self::assertTrue( $this->adapter->deleteMultiple( ['5', '6'] ) );
		self::assertEquals( [], $this->adapter->index() );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_get(): void
	{
//		self::assertNull( $this->adapter->get( 'key1' ) );

		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );

		Editor::saveArray( $this->filePath, ['key1 = "value1"', 'key2 = "value2"'] );
		self::assertEquals( 'value1', $this->adapter->get( 'key1' ) );
		self::assertEquals( 'value2', $this->adapter->get( 'key2' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( 'value1', $this->adapter->get( '1' ) );

		self::assertNull( $this->adapter->get( 'notExistingKey' ) );
	}

	public function test_get_withException1(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->get( '__äöü__' );
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

		Editor::saveArray( $this->filePath, ['key1 = "value1"'] );
		self::assertEquals( ['key1'], $this->adapter->index() );

		Editor::saveArray( $this->filePath, ['key1 = "value1"', 'key2 = "value2"'] );
		self::assertEquals( ['key1', 'key2'], $this->adapter->index() );
		self::assertEquals( ['key1' => 'value1', 'key2' => 'value2'], $this->adapter->getMultiple( ['key1', 'key2'] ) );

		$this->adapter->setContext( 'key' );
		self::assertEquals( ['1', '2'], $this->adapter->index() );
		self::assertEquals( ['1' => 'value1', '2' => 'value2'], $this->adapter->getMultiple( ['1', '2'] ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_index(): void
	{
		Editor::saveArray( $this->filePath, ['key1 = "value1"', 'key2 = "value2"'] );
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

	public function test_set_withException1(): void
	{
		$this->expectException( SimpleCacheInvalidArgumentException::class );
		$this->adapter->set( '__äöü__', 'nothing' );
	}

	public function test_set_withException2(): void
	{
		$this->expectException( SimpleCacheException::class );
		@unlink( $this->filePath );
		/** @noinspection PhpUnhandledExceptionInspection */
		$this->adapter->set( 'test', 'test' );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function test_setMultiple(): void
	{
		$data	= ['key1' => 'value1', 'key2' => 'value2'];
		$this->adapter->setMultiple( $data );
		self::assertEquals( $data, $this->adapter->getMultiple( ['key1', 'key2'] ) );
		self::assertEquals( ['key1' => 'value1'], $this->adapter->getMultiple( ['key1'] ) );
		self::assertEquals( ['key2' => 'value2'], $this->adapter->getMultiple( ['key2'] ) );

		$data	= ['2' => 'value2_updated', '3' => 'value3'];
		$this->adapter->setContext( 'key' );
		$this->adapter->setMultiple( $data );
		self::assertEquals( ['1', '2', '3'], $this->adapter->index() );
		self::assertEquals( 'value2_updated', $this->adapter->get( '2' ) );
		self::assertEquals( 'value3', $this->adapter->get( '3' ) );
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	protected function setUp(): void
	{
		$this->path		= $this->pathTests.'data/tmp/adapter/';
		if( !file_exists( $this->path ) ){
			$folder	= new Folder( $this->path );
			$folder->create();
		}
		$this->filePath	= $this->path.'cache.ini';
		$this->adapter	= new IniFileAdapter( $this->filePath );
	}

	protected function tearDown(): void
	{
		@unlink( $this->filePath );
	}
}