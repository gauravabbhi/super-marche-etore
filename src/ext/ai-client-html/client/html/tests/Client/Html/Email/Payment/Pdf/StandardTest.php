<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2020
 */


namespace Aimeos\Client\Html\Email\Payment\Pdf;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private static $orderItem;
	private static $orderBaseItem;
	private $object;
	private $context;
	private $emailMock;


	public static function setUpBeforeClass() : void
	{
		$manager = \Aimeos\MShop\Order\Manager\Factory::create( \TestHelperHtml::getContext() );
		$orderBaseManager = $manager->getSubManager( 'base' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'order.datepayment', '2008-02-15 12:34:56' ) );

		if( ( self::$orderItem = $manager->searchItems( $search )->first() ) === null ) {
			throw new \RuntimeException( 'No order found' );
		}

		self::$orderBaseItem = $orderBaseManager->load( self::$orderItem->getBaseId() );
	}


	protected function setUp() : void
	{
		$this->context = \TestHelperHtml::getContext();
		$this->emailMock = $this->getMockBuilder( '\\Aimeos\\MW\\Mail\\Message\\None' )->getMock();

		$view = \TestHelperHtml::getView( 'unittest', $this->context->getConfig() );
		$view->extOrderItem = self::$orderItem;
		$view->extOrderBaseItem = self::$orderBaseItem;
		$view->addHelper( 'mail', new \Aimeos\MW\View\Helper\Mail\Standard( $view, $this->emailMock ) );

		$this->object = new \Aimeos\Client\Html\Email\Payment\Pdf\Standard( $this->context );
		$this->object->setView( $view );
	}


	protected function tearDown() : void
	{
		unset( $this->object );
	}


	public function testGetBody()
	{
		$this->emailMock->expects( $this->once() )->method( 'addAttachment' );

		$this->object->setView( $this->object->addData( $this->object->getView() ) );
		$this->object->getBody();
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
}
