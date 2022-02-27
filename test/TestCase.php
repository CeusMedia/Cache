<?php
namespace CeusMedia\Cache\Test;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use UI_DevOutput;

class TestCase extends PhpUnitTestCase
{
	protected $pathLibrary;
	protected $pathTests;

	public function __construct( $name = NULL )
	{
		parent::__construct( $name );
		new UI_DevOutput();
		$this->pathLibrary		= dirname( __DIR__ ).'/';
		$this->pathTests		= __DIR__.'/';
	}

	//  --  PROTECTED  --  //
}
