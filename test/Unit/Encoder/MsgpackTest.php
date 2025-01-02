<?php /** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace CeusMedia\CacheTest\Unit\Encoder;

use CeusMedia\Cache\Encoder\Msgpack as MsgpackEncoder;
use CeusMedia\CacheTest\TestCase;

class MsgpackTest extends TestCase
{
	public function test_decode(): void
	{
		$this->needsMsgpackExtension();
		self::assertNull( MsgpackEncoder::decode( msgpack_pack( NULL ) ) );
		self::assertTrue( MsgpackEncoder::decode( msgpack_pack( TRUE ) ) );
		self::assertFalse( MsgpackEncoder::decode( msgpack_pack( FALSE ) ) );
		self::assertEquals( 1, MsgpackEncoder::decode( msgpack_pack( 1 ) ) );
		self::assertEquals( -1.234, MsgpackEncoder::decode( msgpack_pack( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', MsgpackEncoder::decode( msgpack_pack( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, MsgpackEncoder::decode( msgpack_pack( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, MsgpackEncoder::decode( msgpack_pack( $object ) ) );
	}

	public function test_encode(): void
	{
		$this->needsMsgpackExtension();
		self::assertNull( msgpack_unpack( MsgpackEncoder::encode( NULL ) ) );
		self::assertTrue( msgpack_unpack( MsgpackEncoder::encode( TRUE ) ) );
		self::assertFalse( msgpack_unpack( MsgpackEncoder::encode( FALSE ) ) );
		self::assertEquals( 1, msgpack_unpack( MsgpackEncoder::encode( 1 ) ) );
		self::assertEquals( -1.234, msgpack_unpack( MsgpackEncoder::encode( -1.234 ) ) );
		self::assertEquals( 'ABCÄÖÜ', msgpack_unpack( MsgpackEncoder::encode( 'ABCÄÖÜ' ) ) );

		$array	= ['a' => 1, 'b' => [1, 2, 3]];
		self::assertEquals( $array, msgpack_unpack( MsgpackEncoder::encode( $array ) ) );

		$object	= (object) $array;
		self::assertEquals( $object, msgpack_unpack( MsgpackEncoder::encode( $object ) ) );
	}

	protected function needsMsgpackExtension(): void
	{
		if( !extension_loaded( 'msgpack' ) ){
			self::markTestSkipped( 'The msgpack extension is not installed. Please install the extension to enable '.__CLASS__ );
		}
	}
}