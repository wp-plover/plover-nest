<?php

namespace Plover\Nest;

/**
 * Plover Nest framework bootstrapper
 *
 * @since 1.0.0
 */
class Bootstrap {

	/**
	 * Is booted or not.
	 *
	 * @var bool
	 */
	protected static $booted = false;
	/**
	 * Service providers.
	 *
	 * @var array
	 */
	protected static $providers = [];
	/**
	 * Nest instance.
	 *
	 * @var Nest
	 */
	protected $nest;

	/**
	 * Create bootstrapper instance.
	 *
	 * @param $id
	 * @param $base_path
	 * @param $base_url
	 */
	protected function __construct() {
		// Create new nest framework instance
		$this->nest = new Nest();
	}

	/**
	 * @param $providers
	 *
	 * @return Bootstrap
	 */
	public static function make( $providers = [] ) {
		return ( new static() )->withProviders( $providers );
	}

	/**
	 * Register additional service providers.
	 *
	 * @param array $providers
	 * @param bool $withBootstrapProviders
	 *
	 * @return $this
	 */
	public function withProviders( array $providers = [] ) {
		static::$providers = array_merge(
			static::$providers,
			$providers
		);

		return $this;
	}

	/**
	 * Register an array of container bindings to be bound when the application is booting.
	 *
	 * @param array $bindings
	 *
	 * @return $this
	 */
	public function withBindings( array $bindings ) {
		return $this->registered( function ( $nest ) use ( $bindings ) {
			foreach ( $bindings as $abstract => $concrete ) {
				$nest->bind( $abstract, $concrete );
			}
		} );
	}

	/**
	 * Register a callback to be invoked when the application's service providers are registered.
	 *
	 * @param callable $callback
	 * @param int $priority
	 *
	 * @return $this
	 */
	public function registered( callable $callback, int $priority = 10 ) {
		$this->nest->registered( $callback, $priority );

		return $this;
	}

	/**
	 * Register an array of singleton container bindings to be bound when the application is booting.
	 *
	 * @param array $singletons
	 *
	 * @return $this
	 */
	public function withSingletons( array $singletons ) {
		return $this->registered( function ( $nest ) use ( $singletons ) {
			foreach ( $singletons as $abstract => $concrete ) {
				if ( is_string( $abstract ) ) {
					$nest->singleton( $abstract, $concrete );
				} else {
					$nest->singleton( $concrete );
				}
			}
		} );
	}

	/**
	 * Register a callback to be invoked when the application is "booting".
	 *
	 * @param callable $callback
	 * @param int $priority
	 *
	 * @return $this
	 */
	public function booting( callable $callback, int $priority = 10 ) {
		$this->nest->booting( $callback, $priority );

		return $this;
	}

	/**
	 * Register a callback to be invoked when the application is "booted".
	 *
	 * @param callable $callback
	 * @param int $priority
	 *
	 * @return $this
	 */
	public function booted( callable $callback, int $priority = 10 ) {
		$this->nest->booted( $callback, $priority );

		return $this;
	}

	/**
	 * @return void
	 */
	public function boot() {
		if ( static::$booted ) {
			return;
		}

		static::$booted = true;

		$this->nest->register_providers( static::$providers );
		$this->nest->boot();
	}
}
