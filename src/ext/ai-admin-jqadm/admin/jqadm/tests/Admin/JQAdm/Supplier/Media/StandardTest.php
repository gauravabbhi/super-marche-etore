<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2020
 */


namespace Aimeos\Admin\JQAdm\Supplier\Media;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $context;
	private $object;
	private $view;


	protected function setUp() : void
	{
		$this->view = \TestHelperJqadm::getView();
		$this->context = \TestHelperJqadm::getContext();

		$this->object = new \Aimeos\Admin\JQAdm\Supplier\Media\Standard( $this->context );
		$this->object = new \Aimeos\Admin\JQAdm\Common\Decorator\Page( $this->object, $this->context );
		$this->object->setAimeos( \TestHelperJqadm::getAimeos() );
		$this->object->setView( $this->view );
	}


	protected function tearDown() : void
	{
		unset( $this->object, $this->view, $this->context );
	}


	public function testCreate()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$this->view->item = $manager->createItem();
		$result = $this->object->create();

		$this->assertStringContainsString( 'item-media', $result );
		$this->assertEmpty( $this->view->get( 'errors' ) );
	}


	public function testCopy()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$this->view->item = $manager->findItem( 'unitCode001', ['media'] );
		$result = $this->object->copy();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertStringContainsString( '&quot;media.preview&quot;:&quot;path\/to\/supplier.jpg&quot;', $result );
	}


	public function testDelete()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$this->view->item = $manager->createItem();
		$result = $this->object->delete();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
	}


	public function testGet()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );

		$this->view->item = $manager->findItem( 'unitCode001', ['media'] );
		$result = $this->object->get();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertStringContainsString( '&quot;media.preview&quot;:&quot;path\/to\/supplier.jpg&quot;', $result );
	}


	public function testSave()
	{
		$manager = \Aimeos\MShop::create( $this->context, 'supplier' );
		$this->view->item = $manager->createItem();

		$param = array(
			'site' => 'unittest',
			'media' => [[
				'media.id' => '',
				'media.type' => 'default',
				'media.languageid' => 'de',
				'media.label' => 'test',
				'supplier.lists.type' => 'default',
			]],
		);

		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, $param );
		$this->view->addHelper( 'param', $helper );

		$file = $this->getMockBuilder( \Psr\Http\Message\UploadedFileInterface::class )->getMock();
		$request = $this->getMockBuilder( \Psr\Http\Message\ServerRequestInterface::class )->getMock();
		$request->expects( $this->any() )->method( 'getUploadedFiles' )
			->will( $this->returnValue( ['media' => [0 => ['file' => $file]]] ) );

		$helper = new \Aimeos\MW\View\Helper\Request\Standard( $this->view, $request, '127.0.0.1', 'test' );
		$this->view ->addHelper( 'request', $helper );


		$name = 'AdminJQAdmSupplierMediaSave';
		$this->context->getConfig()->set( 'controller/common/media/name', $name );

		$cntlStub = $this->getMockBuilder( '\\Aimeos\\Controller\\Common\\Media\\Standard' )
			->setConstructorArgs( array( $this->context ) )
			->setMethods( array( 'add' ) )
			->getMock();

		\Aimeos\Controller\Common\Media\Factory::inject( '\\Aimeos\\Controller\\Common\\Media\\' . $name, $cntlStub );

		$cntlStub->expects( $this->once() )->method( 'add' )->will( $this->returnArgument( 0 ) );


		$result = $this->object->save();


		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
		$this->assertEquals( 1, count( $this->view->item->getListItems() ) );

		foreach( $this->view->item->getListItems( 'media' ) as $listItem )
		{
			$this->assertEquals( 'media', $listItem->getDomain() );

			$refItem = $listItem->getRefItem();
			$this->assertEquals( 'de', $refItem->getLanguageId() );
			$this->assertEquals( 'test', $refItem->getLabel() );
		}


		$helper = new \Aimeos\MW\View\Helper\Param\Standard( $this->view, ['site' => 'unittest', 'media' => []] );
		$this->view->addHelper( 'param', $helper );

		$result = $this->object->save();

		$this->assertEmpty( $this->view->get( 'errors' ) );
		$this->assertEmpty( $result );
		$this->assertEquals( 0, count( $this->view->item->getListItems() ) );
	}


	public function testSaveException()
	{
		$object = $this->getClientMock( 'fromArray' );

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \RuntimeException() ) );

		$this->expectException( \RuntimeException::class );
		$object->save();
	}


	public function testSaveMShopException()
	{
		$object = $this->getClientMock( 'fromArray' );

		$object->expects( $this->once() )->method( 'fromArray' )
			->will( $this->throwException( new \Aimeos\MShop\Exception() ) );

		$this->expectException( \Aimeos\MShop\Exception::class );
		$object->save();
	}


	public function testSearch()
	{
		$this->assertEmpty( $this->object->search() );
	}


	public function testGetSubClient()
	{
		$this->expectException( \Aimeos\Admin\JQAdm\Exception::class );
		$this->object->getSubClient( 'unknown' );
	}


	public function getClientMock( $method )
	{
		$object = $this->getMockBuilder( \Aimeos\Admin\JQAdm\Supplier\Media\Standard::class )
			->setConstructorArgs( array( $this->context, \TestHelperJqadm::getTemplatePaths() ) )
			->setMethods( [$method] )
			->getMock();

		$view = \TestHelperJqadm::getView();
		$view->item = \Aimeos\MShop::create( $this->context, 'supplier' )->createItem();

		$object->setAimeos( \TestHelperJqadm::getAimeos() );
		$object->setView( $view );

		return $object;
	}
}
