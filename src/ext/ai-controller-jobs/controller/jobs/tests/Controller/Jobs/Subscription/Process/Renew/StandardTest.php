<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2018-2020
 */


namespace Aimeos\Controller\Jobs\Subscription\Process\Renew;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $aimeos;
	private $context;
	private $object;


	protected function setUp() : void
	{
		$this->aimeos = \TestHelperJobs::getAimeos();
		$this->context = \TestHelperJobs::getContext();

		$this->object = new \Aimeos\Controller\Jobs\Subscription\Process\Renew\Standard( $this->context, $this->aimeos );

		\Aimeos\MShop::cache( true );
	}


	protected function tearDown() : void
	{
		\Aimeos\MShop::cache( false );
		unset( $this->object, $this->context, $this->aimeos );
	}


	public function testGetName()
	{
		$this->assertEquals( 'Subscription process renew', $this->object->getName() );
	}


	public function testGetDescription()
	{
		$this->assertEquals( 'Renews subscriptions at next date', $this->object->getDescription() );
	}


	public function testRun()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );
		$item = $this->getSubscription();

		$object = $this->getMockBuilder( '\\Aimeos\\Controller\\Jobs\\Subscription\\Process\\Renew\\Standard' )
			->setConstructorArgs( [$this->context, $this->aimeos] )
			->setMethods( ['createPayment'] )
			->getMock();

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['searchItems', 'saveItem'] )
			->getMock();

		$orderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		$baseStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['store'] )
			->getMock();

		\Aimeos\MShop::inject( 'subscription', $managerStub );
		\Aimeos\MShop::inject( 'order/base', $baseStub );
		\Aimeos\MShop::inject( 'order', $orderStub );

		$object->expects( $this->once() )->method( 'createPayment' );

		$managerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->returnValue( map( [$item] ) ) );

		$managerStub->expects( $this->once() )->method( 'saveItem' );
		$orderStub->expects( $this->once() )->method( 'saveItem' );
		$baseStub->expects( $this->once() )->method( 'store' )
			->will( $this->returnCallback( function( $basket ) {
				return $basket->setId( -1 );
			} ) );

		$object->run();
	}


	public function testRunException()
	{
		$this->context->getConfig()->set( 'controller/common/subscription/process/processors', ['cgroup'] );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Subscription\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['searchItems', 'saveItem'] )
			->getMock();

		\Aimeos\MShop::inject( 'subscription', $managerStub );

		$managerStub->expects( $this->once() )->method( 'searchItems' )
			->will( $this->returnValue( map( [$managerStub->createItem()->setOrderBaseId( -1 )] ) ) );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$this->object->run();
	}


	public function testAddBasketAddresses()
	{
		$custId = \Aimeos\MShop::create( $this->context, 'customer' )->findItem( 'test@example.com')->getId();
		$basket = \Aimeos\MShop::create( $this->context, 'order/base' )->createItem()->setCustomerId( $custId );
		$address = \Aimeos\MShop::create( $this->context, 'order/base/address' )->createItem();

		$addresses = map( ['delivery' => [$address]] );
		$basket = $this->access( 'addBasketAddresses' )->invokeArgs( $this->object, [$this->context, $basket, $addresses] );

		$this->assertEquals( 2, count( $basket->getAddresses() ) );
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Base\Address\Iface::class, $basket->getAddress( 'payment', 0 ) );
		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Base\Address\Iface::class, $basket->getAddress( 'delivery', 0 ) );
	}


	public function testAddBasketCoupons()
	{
		$this->context->getConfig()->set( 'controller/jobs/subscription/process/renew/standard/use-coupons', true );

		$basket = \Aimeos\MShop::create( $this->context, 'order/base' )->createItem();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'CNC', ['price'] );
		$orderProduct = \Aimeos\MShop::create( $this->context, 'order/base/product' )->createItem();

		$price = $product->getRefItems( 'price', 'default', 'default' )->first();
		$basket->addProduct( $orderProduct->copyFrom( $product )->setPrice( $price )->setStockType( 'default' ) );

		$this->assertEquals( '600.00', $basket->getPrice()->getValue() );
		$this->assertEquals( '30.00', $basket->getPrice()->getCosts() );
		$this->assertEquals( '0.00', $basket->getPrice()->getRebate() );

		$basket = $this->access( 'addBasketCoupons' )->invokeArgs( $this->object, [$this->context, $basket, map( ['90AB'] )] );

		$this->assertEquals( 1, count( $basket->getCoupons() ) );
		$this->assertEquals( 2, count( $basket->getProducts() ) );
		$this->assertEquals( '537.00', $basket->getPrice()->getValue() );
		$this->assertEquals( '30.00', $basket->getPrice()->getCosts() );
		$this->assertEquals( '63.00', $basket->getPrice()->getRebate() );
	}


	public function testAddBasketProducts()
	{
		$basket = \Aimeos\MShop::create( $this->context, 'order/base' )->createItem();
		$product = \Aimeos\MShop::create( $this->context, 'product' )->findItem( 'CNC' );
		$manager = \Aimeos\MShop::create( $this->context, 'order/base/product' );

		$orderProducts = map( [
			$manager->createItem()->copyFrom( $product )->setId( 1 )->setStockType( 'default' ),
			$manager->createItem()->copyFrom( $product )->setId( 2 )->setStockType( 'default' ),
		] );

		$basket = $this->access( 'addBasketProducts' )->invokeArgs( $this->object, [$this->context, $basket, $orderProducts, 1] );

		$this->assertEquals( 1, count( $basket->getProducts() ) );
		$this->assertNull( $basket->getProduct( 0 )->getId() );
	}


	public function testAddBasketServices()
	{
		$basket = \Aimeos\MShop::create( $this->context, 'order/base' )->createItem();
		$manager = \Aimeos\MShop::create( $this->context, 'order/base/service' );

		$orderServices = map( [
			'delivery' => [$manager->createItem()->setCode( 'shiptest' )],
			'payment' => [$manager->createItem()->setCode( 'paytest' )],
		] );

		$basket = $this->access( 'addBasketServices' )->invokeArgs( $this->object, [$this->context, $basket, $orderServices] );

		$class = \Aimeos\MShop\Order\Item\Base\Service\Iface::class;

		$this->assertEquals( 2, count( $basket->getServices() ) );
		$this->assertEquals( 1, count( $basket->getService( 'delivery' ) ) );
		$this->assertInstanceOf( $class, $basket->getService( 'delivery', 0 ) );
		$this->assertEquals( 1, count( $basket->getService( 'payment' ) ) );
		$this->assertInstanceOf( $class, $basket->getService( 'payment', 0 ) );
	}


	public function testCreateOrder()
	{
		$item = $this->getSubscription();

		$result = $this->access( 'createOrder' )->invokeArgs( $this->object, [$this->context, $item] );

		$this->assertInstanceOf( \Aimeos\MShop\Order\Item\Iface::class, $result );
	}


	public function testCreateOrderBase()
	{
		$item = $this->getSubscription();
		$class = \Aimeos\MShop\Order\Item\Base\Iface::class;

		$result = $this->access( 'createOrderBase' )->invokeArgs( $this->object, [$this->context, $item] );

		$this->assertInstanceOf( $class, $result );
	}


	public function testCreateOrderInvoice()
	{
		$item = $this->getSubscription();
		$baseItem = $this->getOrderBaseItem( $item->getOrderBaseId() );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		\Aimeos\MShop::inject( 'order', $managerStub );

		$managerStub->expects( $this->once() )->method( 'saveItem' )->will( $this->returnArgument( 0 ) );

		$this->access( 'createOrderInvoice' )->invokeArgs( $this->object, [$this->context, $baseItem] );
	}


	public function testCreatePayment()
	{
		$item = $this->getSubscription();
		$invoice = $this->getOrderItem();
		$baseItem = $this->getOrderBaseItem( $item->getOrderBaseId() );

		$managerStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( [$this->context] )
			->setMethods( ['saveItem'] )
			->getMock();

		\Aimeos\MShop::inject( 'order', $managerStub );

		$managerStub->expects( $this->once() )->method( 'saveItem' );

		$this->access( 'createPayment' )->invokeArgs( $this->object, [$this->context, $baseItem, $invoice] );
	}


	protected function getOrderItem()
	{
		return \Aimeos\MShop::create( $this->context, 'order' )->createItem();
	}


	protected function getOrderBaseItem( $baseId )
	{
		return \Aimeos\MShop::create( $this->context, 'order/base' )->getItem( $baseId, ['order/base/service'] );
	}


	protected function getSubscription()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'subscription' );

		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'subscription.dateend', '2010-01-01' ) );

		if( ( $item = $manager->searchItems( $search )->first() ) !== null ) {
			return $item;
		}

		throw new \Exception( 'No subscription item found' );
	}


	protected function access( $name )
	{
		$class = new \ReflectionClass( \Aimeos\Controller\Jobs\Subscription\Process\Renew\Standard::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );

		return $method;
	}
}
