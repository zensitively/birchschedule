<?php

if ( !function_exists( 'birch_ns' ) ) {

	function birch_assert( $assertion, $message = '' ) {
		if ( !$assertion ) {
			throw new ErrorException( $message );
		}
	}

	function birch_log() {
		$args = func_get_args();
		$message = '';
		foreach ( $args as $arg ) {
			if ( is_array( $arg ) || is_object( $arg ) ) {
				$message .= print_r( $arg, true );
			} else {
				$message .= $arg;
			}
		}
		error_log( $message );
	}

	class Birch_Fn extends stdClass {
		var $ns;
		var $name;
		var $fns;
		var $match_fns;
		var $default_fn;

		function __construct( $config ) {
			$this->ns = $config['ns'];
			$this->name = $config['name'];
			$this->fns = array();
			$this->match_fns = array();
			if ( is_callable( $config['fn'] ) ) {
				$this->default_fn = $config['fn'];
			} else {
				$this->default_fn = function() {};
			}
		}

		function _set_default( $value ) {
			if ( !is_callable( $value ) || is_a( $value, 'Closure' ) ) {
				throw new ErrorException( sprintf( 'Redefined function must be a callable and cannot be an anonymous function.' ) );
			}
			$this->default_fn = $value;
		}

		function call_default() {
			$args = func_get_args();
			return call_user_func_array( $this->default_fn, $args );
		}

		function when( $match_fn, $func ) {
			if ( !is_callable( $match_fn ) || is_a( $match_fn, 'Closure' ) ) {
				throw new ErrorException( sprintf( 'Matching function(1st arg) must be a callable and cannot be an anonymous function.' ) );
			}
			if ( !is_callable( $func ) || is_a( $func, 'Closure' ) ) {
				throw new ErrorException( sprintf( 'Method(2nd arg) must be a callable and cannot be an anonymous function.' ) );
			}
			$index = array_search( $match_fn, $this->match_fns, true );
			if ( $index || $index === 0 ) {
				$this->fns[$index] = $func;
			} else {
				$this->match_fns[] = $match_fn;
				$this->fns[] = $func;
			}
		}

		function _find_real_function( $args ) {
			$index = 0;
			foreach ( $this->match_fns as $match_fn ) {
				$matched = call_user_func_array( $match_fn, $args );
				if ( $matched ) {
					return $this->fns[$index];
				}
				$index++;
			}
			return $this->default_fn;
		}

		function get_full_name() {
			return $this->ns . '.' . $this->name;
		}

		function get_message_name() {
			return str_replace( '.', '_', $this->ns ) . '_' . $this->name;
		}

		function get_call_string() {
			return '$' . str_replace( '.', '->', $this->ns ) . '->' . $this->name;
		}

		function __invoke() {
			$args = func_get_args();
			$message_name = $this->get_message_name();

			$event_before = $message_name . '_before';
			if ( has_action( $event_before ) ) {
				do_action_ref_array( $event_before, $args );
			}

			$event_pre = $message_name . '_args';
			if ( has_filter( $event_pre ) ) {
				$args = apply_filters_ref_array( $event_pre, $args );
			}

			$result = null;
			$fn = $this->_find_real_function( $args );
			if ( is_callable( $fn ) ) {
				$result = call_user_func_array( $fn, $args );
			}
			if ( has_filter( $message_name ) ) {
				$new_args = array_merge( array( $result ), $args );
				$result = apply_filters_ref_array( $message_name, $new_args );
			}

			$event_after = $message_name . '_after';
			if ( has_action( $event_after ) ) {
				do_action_ref_array( $event_after, array_merge( $args, array( $result ) ) );
			}

			return $result;
		}

		function __toString() {
			return $this->get_call_string();
		}
	}

	class Birch_NSObject extends stdClass implements ArrayAccess {

		var $sub_ns_keys = array();

		var $data = array();

		var $ns_string = '';

		function __construct( $ns_string ) {
			$this->ns_string = $ns_string;
		}

		function __get( $key ) {
			if ( !isset( $this->data[$key] ) ) {
				throw new ErrorException( sprintf( '<%s> is undefined in namespace <%s>', $key, $this->ns_string ) );
			}
			return $this->data[$key];
		}

		function __set( $key, $value ) {
			if ( !_birch_is_valid_var_name( $key ) ) {
				throw new ErrorException( sprintf(
						'String <%s> is invalid as the sub-namespace or function name in namespace <%s>',
						$key, $this->ns_string ) );
			}
			if ( !is_a( $value, 'Birch_NSObject' ) && !is_callable( $value ) ) {
				throw new ErrorException(
					sprintf( 'Namespace <%s> can only has namespace object or callable.' .
						' The given value is <%s>', $this->ns_string, $value ) );
			}
			if ( isset( $this->data[$key] ) && is_a( $this->data[$key], 'Birch_NSObject' ) ) {
				throw new ErrorException(
					sprintf( '<%s> has been defined in Namespace <%s> as a sub-namespace and cannot be redefined.',
						$key, $this->ns_string ) );
			}
			if ( is_a( $value, 'Birch_NSObject' ) ) {
				$pos = strpos( $value->ns_string, $this->ns_string );
				if ( $pos === false || $pos !== 0 ) {
					throw new ErrorException(
						sprintf( 'Namespace <%s> is not a sub namespace of namespace <%s>',
							$value->ns_string, $this->ns_string ) );
				}
				$this->data[$key] = $value;
				if ( !in_array( $key, $this->sub_ns_keys ) ) {
					$this->sub_ns_keys[] = $key;
				}
			} elseif ( is_callable( $value ) ) {
				if ( isset( $this->data[$key] ) ) {
					$this->data[$key]->_set_default( $value );
				} else {
					$config = array(
						'ns' => $this,
						'name' => $key,
						'fn' => $value
					);
					$this->data[$key] = new Birch_Fn( $config );
				}
			}
		}

		function __isset( $key ) {
			return isset( $this->data[$key] );
		}

		function __unset( $key ) {
			unset( $this->data[$key] );
		}

		function get_sub_ns_keys() {
			return $this->sub_ns_keys;
		}

		function __toString() {
			return $this->ns_string;
		}

		function offsetExists( $key ) {
			return $this->__isset( $key );
		}

		function offsetGet( $key ) {
			return $this->__get( $key );
		}

		function offsetSet( $key, $value ) {
			$this->__set( $key, $value );
		}

		function offsetUnset( $key ) {
			$this->__unset( $key );
		}

		function __call( $method, $args ) {
			if ( !isset( $this->data[$method] ) ) {
				throw new ErrorException( sprintf( '<%s> is undefined in namespace <%s>', $method, $this->ns_string ) );
			}
			return call_user_func_array( $this->data[$method], $args );
		}

	}

	function _birch_is_valid_var_name( $name ) {
		return preg_match( '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $name );
	}

	function birch_ns( $ns_name, $init_func = false ) {
		birch_assert( is_string( $ns_name ), sprintf( 'The namespace <%s> should be string.', $ns_name ) );

		$ns = explode( '.', $ns_name );
		$current_str = $ns[0];
		if ( !isset( $GLOBALS[$current_str] ) ) {
			$GLOBALS[$current_str] = new Birch_NSObject( $current_str );
		}
		$current = $GLOBALS[$current_str];
		$subs = array_slice( $ns, 1 );
		foreach ( $subs as $sub ) {
			$current_str .= '.' . $sub;
			if ( !isset( $current[$sub] ) ) {
				$current[$sub] = new Birch_NSObject( $current_str );
			}
			$current = $current[$sub];
		}
		if ( is_callable( $init_func ) ) {
			global $birch_current_ns;

			$birch_current_ns = $current;
			$init_func( $current );
			$birch_current_ns = false;
		}
		return $current;
	}


	//Begin - for backward compatibility
	function birch_defn( $ns, $func_name, $func ) {
		if ( !is_a( $ns, 'Birch_NSObject' ) ) {
			throw new ErrorException( sprintf(
					'<%s> is not a namespace object.',
					$ns ) );
		}
		$ns->$func_name = $func;
	}
	//End - backward compatibity

}
