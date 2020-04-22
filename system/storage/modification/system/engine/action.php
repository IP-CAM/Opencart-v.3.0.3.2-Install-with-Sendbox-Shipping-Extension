<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Action class
*/
class Action {
	private $id;
	private $route;
	private $method = 'index';
	
	/**
	 * Constructor
	 *
	 * @param	string	$route
 	*/
	public function __construct($route) {
		$this->id = $route;
		
		$parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route));

		// Break apart the route
		while ($parts) {
			$file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';

			if (is_file($file)) {
				$this->route = implode('/', $parts);		
				
				break;
			} else {
				$this->method = array_pop($parts);
			}
		}
	}

	/**
	 * 
	 *
	 * @return	string
	 *
 	*/	
	public function getId() {
		return $this->id;
	}

	protected function override_execute($registry, array $args = array()) {
		// Stop any magical methods being called
		if (substr($this->method, 0, 2) == '__') {
			return new \Exception('Error: Calls to magic methods are not allowed!');
		}

		if (!$registry->get('factory')) {
			require_once(DIR_SYSTEM . 'library/override/factory.php');
			$registry->set('factory',new Factory($registry));
		}
		$factory = $registry->get('factory');

		$properties = $factory->actionProperties($this->id);
		$route = $this->route;
		$file = isset($properties['file']) ? $properties['file'] : DIR_APPLICATION . 'controller/' . $route . '.php';
		$class = isset($properties['class']) ? $properties['class'] : $class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $route);
		$method = isset($properties['method']) ? $properties['method'] : $this->method;

		if (is_file($file)) {
			$controller = $factory->newController( $file, $class );
		} else {
			return new \Exception('Error: Could not call ' . $route . '/' . $method . '!');
		}

		$class = get_class($controller);
		$reflection = new ReflectionClass($class);
		if ($reflection->hasMethod($method) && $reflection->getMethod($method)->getNumberOfRequiredParameters() <= count($args)) {
			return call_user_func_array(array($controller, $method), $args);
		} else {
			return new \Exception('Error: Could not call ' . $route . '/' . $method . '!');
		}
	}
			
	
	/**
	 * 
	 *
	 * @param	object	$registry
	 * @param	array	$args
 	*/	
	public function execute($registry, array $args = array()) {

		return $this->override_execute($registry,$args);
			
		// Stop any magical methods being called
		if (substr($this->method, 0, 2) == '__') {
			return new \Exception('Error: Calls to magic methods are not allowed!');
		}

		$file  = DIR_APPLICATION . 'controller/' . $this->route . '.php';	
		$class = 'Controller' . preg_replace('/[^a-zA-Z0-9]/', '', $this->route);
		
		// Initialize the class
		if (is_file($file)) {
			include_once(modification($file));
		
			$controller = new $class($registry);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
		
		$reflection = new ReflectionClass($class);
		
		if ($reflection->hasMethod($this->method) && $reflection->getMethod($this->method)->getNumberOfRequiredParameters() <= count($args)) {
			return call_user_func_array(array($controller, $this->method), $args);
		} else {
			return new \Exception('Error: Could not call ' . $this->route . '/' . $this->method . '!');
		}
	}
}
