<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\Html\Account\Review;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		$this->context = \TestHelperHtml::getContext();

		$this->object = new \Aimeos\Client\Html\Account\Review\Standard( $this->context );
		$this->object->setView( \TestHelperHtml::getView() );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testGetHeader()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->context->setUserId( $manager->findItem( 'test@example.com' )->getId() );

		$output = $this->object->getHeader();
		$this->assertNotNull( $output );
	}


	public function testGetHeaderException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\Html\Account\Review\Standard::class )
			->setConstructorArgs( array( $this->context, [] ) )
			->setMethods( array( 'addData' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'addData' )
			->will( $this->throwException( new \RuntimeException() ) );

		$object->setView( \TestHelperHtml::getView() );

		$this->assertEquals( null, $object->getHeader() );
	}


	public function testGetBody()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->context->setUserId( $manager->findItem( 'test@example.com' )->getId() );

		$output = $this->object->getBody();
		$this->assertStringStartsWith( '<section class="aimeos account-review"', $output );
	}


	public function testGetBodyHtmlException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\Html\Account\Review\Standard::class )
			->setConstructorArgs( array( $this->context, [] ) )
			->setMethods( array( 'addData' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'addData' )
			->will( $this->throwException( new \Aimeos\Client\Html\Exception( 'test exception' ) ) );

		$object->setView( \TestHelperHtml::getView() );

		$this->assertStringContainsString( 'test exception', $object->getBody() );
	}


	public function testGetBodyFrontendException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\Html\Account\Review\Standard::class )
			->setConstructorArgs( array( $this->context, [] ) )
			->setMethods( array( 'addData' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'addData' )
			->will( $this->throwException( new \Aimeos\Controller\Frontend\Exception( 'test exception' ) ) );

		$object->setView( \TestHelperHtml::getView() );

		$this->assertStringContainsString( 'test exception', $object->getBody() );
	}


	public function testGetBodyMShopException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\Html\Account\Review\Standard::class )
			->setConstructorArgs( array( $this->context, [] ) )
			->setMethods( array( 'addData' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'addData' )
			->will( $this->throwException( new \Aimeos\MShop\Exception( 'test exception' ) ) );

		$object->setView( \TestHelperHtml::getView() );

		$this->assertStringContainsString( 'test exception', $object->getBody() );
	}


	public function testGetBodyException()
	{
		$object = $this->getMockBuilder( \Aimeos\Client\Html\Account\Review\Standard::class )
			->setConstructorArgs( array( $this->context, [] ) )
			->setMethods( array( 'addData' ) )
			->getMock();

		$object->expects( $this->once() )->method( 'addData' )
			->will( $this->throwException( new \RuntimeException() ) );

		$object->setView( \TestHelperHtml::getView() );

		$this->assertStringContainsString( 'A non-recoverable error occured', $object->getBody() );
	}


	public function testGetSubClientInvalid()
	{
		$this->expectException( '\\Aimeos\\Client\\Html\\Exception' );
		$this->object->getSubClient( 'invalid', 'invalid' );
	}


	public function testGetSubClientInvalidName()
	{
		$this->expectException( '\\Aimeos\\Client\\Html\\Exception' );
		$this->object->getSubClient( '$$$', '$$$' );
	}


	public function testProcess()
	{
		$this->object->process();

		$this->assertEmpty( $this->object->getView()->get( 'reviewErrorList' ) );
	}
}
