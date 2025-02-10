<?php
declare(strict_types=1);

namespace CeusMedia\CacheTest\Unit\Encoder;

use CeusMedia\Cache\Encoder\Serial as SerialEncoder;
use CeusMedia\CacheTest\TestCase;

class SerialTest extends TestCase
{
	public function test_decode(): void
	{
		self::assertNull( SerialEncoder::decode( serialize( NULL ) ) );
		self::assertEquals( 1, SerialEncoder::decode( serialize( 1 ) ) );
		self::assertEquals( -1.234, SerialEncoder::decode( serialize( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', SerialEncoder::decode( serialize( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, SerialEncoder::decode( serialize( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, SerialEncoder::decode( serialize( $object ) ) );
	}

	public function test_encode(): void
	{
		self::assertNull( unserialize( SerialEncoder::encode( NULL ) ) );
		self::assertTrue( unserialize( SerialEncoder::encode( TRUE ) ) );
		self::assertFalse( unserialize( SerialEncoder::encode( FALSE ) ) );
		self::assertEquals( 1, unserialize( SerialEncoder::encode( 1 ) ) );
		self::assertEquals( -1.234, unserialize( SerialEncoder::encode( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', unserialize( SerialEncoder::encode( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, unserialize( SerialEncoder::encode( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, unserialize( SerialEncoder::encode( $object ) ) );
	}
}