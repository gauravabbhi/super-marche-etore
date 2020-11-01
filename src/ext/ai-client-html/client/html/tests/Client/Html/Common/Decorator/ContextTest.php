<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\Html\Common\Decorator;


class ContextTest extends \PHPUnit\Framework\TestCase
{
	private $client;
	private $object;


	protected function setUp() : void
	{
		$context = \TestHelperHtml::getContext();

		$this->client = $this->getMockBuilder( '\\Aimeos\\Client\\Html\\Catalog\\Filter\\Standard' )
			->setConstructorArgs( [$context] )
			->setMethods( ['addData'] )
			->getMock();

		$this->object = new \Aimeos\Client\Html\Common\Decorator\Context( $this->client, $context );
		$this->object->setView( \TestHelperHtml::getView() );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->client );
	}


	public function testAddData()
	{
		$this->client->expects( $this->once() )->method( 'addData' ) ->will( $this->returnArgument( 0 ) );

		$result = $this->object->addData( \TestHelperHtml::getView() );

		$this->assertInstanceOf( '\Aimeos\MW\View\Iface', $result );
		$this->assertEquals( 'unittest', $result->get( 'contextSite' ) );
		$this->assertIsString( $result->get( 'contextSiteId' ) );
		$this->assertEquals( 'de', $result->get( 'contextLanguage' ) );
		$this->assertEquals( 'EUR', $result->get( 'contextCurrency' ) );
		$this->assertEquals( null, $result->get( 'contextUserId' ) );
		$this->assertEquals( [], $result->get( 'contextGroupIds' ) );
	}
}
