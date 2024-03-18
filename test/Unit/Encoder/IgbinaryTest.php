<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace CeusMedia\CacheTest\Unit\Encoder;

use CeusMedia\Cache\Encoder\Igbinary as IgbinaryEncoder;
use CeusMedia\CacheTest\TestCase;

class IgbinaryTest extends TestCase
{
	public function test_decode(): void
	{
		$this->needsIgbinaryExtension();
		self::assertNull( IgbinaryEncoder::decode( (string) igbinary_serialize( NULL ) ) );
		self::assertTrue( IgbinaryEncoder::decode( (string) igbinary_serialize( TRUE ) ) );
		self::assertFalse( IgbinaryEncoder::decode( (string) igbinary_serialize( FALSE ) ) );
		self::assertEquals( 1, IgbinaryEncoder::decode( (string) igbinary_serialize( 1 ) ) );
		self::assertEquals( -1.234, IgbinaryEncoder::decode( (string) igbinary_serialize( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', IgbinaryEncoder::decode( (string) igbinary_serialize( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, IgbinaryEncoder::decode( (string) igbinary_serialize( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, IgbinaryEncoder::decode( (string) igbinary_serialize( $object ) ) );
	}

	public function test_encode(): void
	{
		$this->needsIgbinaryExtension();
		self::assertNull( igbinary_unserialize( IgbinaryEncoder::encode( NULL ) ) );
		self::assertTrue( igbinary_unserialize( IgbinaryEncoder::encode( TRUE ) ) );
		self::assertFalse( igbinary_unserialize( IgbinaryEncoder::encode( FALSE ) ) );
		self::assertEquals( 1, igbinary_unserialize( IgbinaryEncoder::encode( 1 ) ) );
		self::assertEquals( -1.234, igbinary_unserialize( IgbinaryEncoder::encode( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', igbinary_unserialize( IgbinaryEncoder::encode( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, igbinary_unserialize( IgbinaryEncoder::encode( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, igbinary_unserialize( IgbinaryEncoder::encode( $object ) ) );
	}

	protected function needsIgbinaryExtension(): void
	{
		if( !extension_loaded( 'igbinary' ) ){
			self::markTestSkipped( 'The igbinary extension is not installed. Please install the extension to enable '.__CLASS__ );
		}
	}
}