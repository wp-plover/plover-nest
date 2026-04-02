<?php

namespace Plover\Nest;

use Plover\Nest\Container\Container;
use Plover\Nest\ServiceProvider;

/**
 * @since 1.0.0
 */
class Nest extends Container {

	/**
	 * The app instance.
	 *
	 * @var Nest|null
	 */
	protected static $app_instance = null;

	/**
	 * Indicates if the app has "booted".
	 *
	 * @var bool
	 */
	protected $booted = false;

	/**
	 * All the registered service providers.
	 *
	 * @var array<string, \Plover\Nest\ServiceProvider>
	 */
	protected $service_provides = [];

	/**
	 * The names of the loaded service providers.
	 *
	 * @var array
	 */
	protected $loaded_providers = [];

	/**
	 * The array of registered callbacks.
	 *
	 * @var callable[]
	 */
	protected $registered_callbacks = [];

	/**
	 * The array of booting callbacks.
	 *
	 * @var array callable[]
	 */
	protected $booting_callbacks = [];

	/**
	 * The array of booted callbacks.
	 *
	 * @var array callable[]
	 */
	protected $booted_callbacks = [];

	/**
	 * Create a new plover nest app instance.
	 *
	 */
	public function __construct() {
		$this->register_base_bindings();
	}

	/**
	 * Register the basic bindings into the container.
	 *
	 * @return void
	 */
	protected function register_base_bindings() {
		static::set_instance( $this );

		$this->instance( Nest::class, $this );
		$this->instance( Container::class, $this );
	}

	/**
	 * Set the shared instance of plover/nest.
	 *
	 * @param Nest $app
	 *
	 * @return Nest
	 */
	protected static function set_instance( $app ) {
		return static::$app_instance = $app;
	}

	/**
	 * Register a service provider with the core.
	 *
	 * @param $provider
	 * @param $force
	 *
	 * @return \Plover\Nest\ServiceProvider
	 */
	public function register( $provider, $force = false ) {
		if ( ( $registered = $this->get_provider( $provider ) ) && ! $force ) {
			return $registered;
		}

		// If the given "provider" is a string, we will resolve it, passing in the
		// application instance automatically. This is simply
		// a more convenient way of specifying our service provider classes.
		if ( is_string( $provider ) ) {
			$provider = $this->resolve_provider( $provider );
		}

		$provider->register();

		// If there are bindings / singletons / aliases set as properties on the provider we
		// will spin through them and register them with the application, which
		// serves as a convenience layer while registering a lot of bindings.
		if ( property_exists( $provider, 'bindings' ) ) {
			foreach ( $provider->bindings as $key => $value ) {
				$this->bind( $key, $value );
			}
		}

		if ( property_exists( $provider, 'singletons' ) ) {
			foreach ( $provider->singletons as $key => $value ) {
				$key = is_int( $key ) ? $value : $key;

				$this->singleton( $key, $value );
			}
		}

		if ( property_exists( $provider, 'aliases' ) ) {
			foreach ( $provider->aliases as $key => $value ) {
				$key = is_int( $key ) ? $value : $key;

				$this->alias( $key, $value );
			}
		}

		$this->mark_as_registered( $provider );

		// If the application has already booted, we will call this boot method on
		// the provider class so it has an opportunity to do its boot logic.
		if ( $this->is_booted() ) {
			$this->boot_provider( $provider );
		}

		return $provider;
	}

	/**
	 * Get the registered service provider if it exists.
	 *
	 * @param $provider
	 *
	 * @return \Plover\Nest\ServiceProvider|null
	 */
	public function get_provider( $provider ) {
		$name = is_string( $provider ) ? $provider : get_class( $provider );

		return $this->service_provides[ $name ] ?? null;
	}

	/**
	 * Resolve a service provider instance from the class name.
	 *
	 * @param string $provider
	 *
	 * @return \Plover\Nest\ServiceProvider
	 */
	protected function resolve_provider( $provider ) {
		return new $provider( $this );
	}

	/**
	 * Mark the given provider as registered.
	 *
	 * @param \Plover\Nest\ServiceProvider $provider
	 *
	 * @return void
	 */
	protected function mark_as_registered( $provider ) {
		$class = get_class( $provider );

		$this->service_provides[ $class ] = $provider;

		$this->loaded_providers[ $class ] = true;
	}

	/**
	 * Determine if the core has booted.
	 *
	 * @return bool
	 */
	public function is_booted() {
		return $this->booted;
	}

	/**
	 * Boot the given service provider.
	 *
	 * @param ServiceProvider $provider
	 *
	 * @return void
	 */
	protected function boot_provider( ServiceProvider $provider ) {
		$provider->call_booting_callbacks();

		if ( method_exists( $provider, 'boot' ) ) {
			$this->call( [ $provider, 'boot' ] );
		}

		$provider->call_booted_callbacks();
	}

	/**
	 * Get the saved globally available instance of plover/core.
	 *
	 * @return mixed|null
	 */
	public static function get_instance() {
		return static::$app_instance;
	}

	/**
	 * Register all the configured providers.
	 *
	 * @return void
	 */
	public function register_providers( $providers ) {
		foreach ( $providers as $provider ) {
			$this->register( $provider );
		}

		$this->fire_callbacks( $this->registered_callbacks );
	}

	/**
	 * Call the booting callbacks for the application.
	 *
	 * @param callable[][] $callbacks
	 *
	 * @return void
	 */
	protected function fire_callbacks( array &$callbacks ) {
		$priorities = array_keys( $callbacks );
		sort( $priorities );

		foreach ( $priorities as $priority ) {
			foreach ( $callbacks[ $priority ] as $callback ) {
				$this->call( $callback );
			}
		}
	}

	/**
	 * Register a new registered listener.
	 *
	 * @param callable $callback
	 * @param $priority
	 *
	 * @return void
	 */
	public function registered( $callback, $priority = 10 ) {
		$this->registered_callbacks[ $priority ][] = $callback;
	}

	/**
	 * Register a new boot listener.
	 *
	 * @param callable $callback
	 * @param $priority
	 *
	 * @return void
	 */
	public function booting( $callback, $priority = 10 ) {
		$this->booting_callbacks[ $priority ][] = $callback;
	}

	/**
	 * Register a new "booted" listener.
	 *
	 * @param callable $callback
	 * @param $priority
	 *
	 * @return void
	 */
	public function booted( $callback, $priority = 10 ) {
		$this->booted_callbacks[ $priority ][] = $callback;

		if ( $this->is_booted() ) {
			$callback( $this );
		}
	}

	/**
	 * Boot the container's service providers.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->is_booted() ) {
			return;
		}

		$this->fire_callbacks( $this->booting_callbacks );

		array_walk( $this->service_provides, function ( $p ) {
			$this->boot_provider( $p );
		} );

		$this->booted = true;

		$this->fire_callbacks( $this->booted_callbacks );
	}
}
