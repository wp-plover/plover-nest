<?php

namespace Plover\Nest\Support;

use Plover\Nest\Container\Container;

/**
 * @since 1.1.0
 */
abstract class Facade {
	/**
	 * Container instance
	 * 
	 * @var Container
	 */
	protected static $container;

	/**
	 * Cache resolved service instances
	 */
	protected static $resolvedInstances = [];

	/**
	 * Set container
	 */
	public static function setContainer( $container ) {
		static::$container = $container;
	}

	/**
	 * Proxy of real service
	 * 
	 * @param mixed $method
	 * @param mixed $args
	 * @throws \RuntimeException
	 * 
	 * @return mixed
	 */
	public static function __callStatic( $method, $args ) {
		$instance = static::getFacadeRoot();
		if ( ! $instance ) {
			throw new \RuntimeException();
		}
		return $instance->$method( ...$args );
	}

	/**
	 * Get real service
	 * 
	 * @return mixed
	 */
	public static function getFacadeRoot() {
		$accessor = static::getFacadeAccessor();
		if ( isset( static::$resolvedInstances[ $accessor ] ) ) {
			return static::$resolvedInstances[ $accessor ];
		}

		$instance = static::$container->make( $accessor );
		static::$resolvedInstances[ $accessor ] = $instance;

		return $instance;
	}

    /**
     * @param mixed $instance
     * @return void
     */
	public static function swap( $instance ) {
		$accessor = static::getFacadeAccessor();
		static::$resolvedInstances[ $accessor ] = $instance;
		static::$container->instance( $accessor, $instance );
	}

	/**
	 * Real service
	 * 
	 * @return mixed
	 */
	abstract protected static function getFacadeAccessor();
}
