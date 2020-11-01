<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2020
 * @package MW
 * @subpackage View
 */


namespace Aimeos\MW\View\Engine;


/**
 * Blade view engine implementation
 *
 * @package MW
 * @subpackage View
 */
class Blade implements Iface
{
	private $factory;


	/**
	 * Initializes the view object
	 *
	 * @param \Illuminate\View\Factory $factory Laravel view factory
	 */
	public function __construct( \Illuminate\View\Factory $factory )
	{
		$this->factory = $factory;
	}


	/**
	 * Renders the output based on the given template file name and the key/value pairs
	 *
	 * @param \Aimeos\MW\View\Iface $view View object
	 * @param string $filename File name of the view template
	 * @param array $values Associative list of key/value pairs
	 * @return string Output generated by the template
	 * @throws \Aimeos\MW\View\Exception If the template isn't found
	 */
	public function render( \Aimeos\MW\View\Iface $view, string $filename, array $values ) : string
	{
		$factory = $this->factory;
		$lv = $factory->file( $filename, $values );

		$fcn = function() use ( $factory, $view )
		{
			foreach( $factory->getSections() as $name => $section ) {
				$view->block()->set( $name, $section );
			}
		};

		$contents = $lv->render( $fcn );
		$this->factory->flushSections();

		return $contents;
	}
}
