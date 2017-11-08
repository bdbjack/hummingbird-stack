<?php
	namespace Hummingbird;

	class HummingbirdWebsocketInterface {
		private $client;

		function __construct( $host, $port, $namespace = '/', $majorVersion = 2, array $headers = array() ) {
			if ( '/' == substr( $host, -1 ) ) {
				$host = substr( $host, 0, strlen( $host ) - 1 );
			}
			$port = intval( $port );
			$namespace = ( '/' == substr( $namespace, 0, 1 ) ) ? $namespace : '/' . $namespace;
			if ( ! __hba_is_empty( $host ) && ! __hba_is_empty( $port ) ) {
				try {
					if ( 2 == intval( $majorVersion ) ) {
						$this->client = new \ElephantIO\Client( new \ElephantIO\Engine\SocketIO\Version2X( sprintf( '%s:%s', $host, $port ), $headers ) );
					}
					else if ( 1 == intval( $majorVersion ) ) {
						$this->client = new \ElephantIO\Client( new \ElephantIO\Engine\SocketIO\Version1X( sprintf( '%s:%s', $host, $port ), $headers ) );
					}
					else {
						$this->client = new \ElephantIO\Client( new \ElephantIO\Engine\SocketIO\Version0X( sprintf( '%s:%s', $host, $port ), $headers ) );
					}
					if ( '/' !== $namespace ) {
						$this->client->of( $namespace );
					}
				}
				catch ( \Exception $e ) {}
			}
		}

		function emit( $key, $value ) {
			if ( ! is_object( $this->client ) ) {
				return false;
			}
			$return = false;
			if ( is_object( $this->client ) ) {
				try {
					$this->client->initialize();
					$return = $this->client->emit( $key, $value );
					$this->client->close();
				}
				catch ( \Exception $e ){}
			}
			return $return;
		}

		function listen( $callback ) {
			if ( ! is_object( $this->client ) ) {
				return false;
			}
			try {
				$this->client->initialize();
				$continue = true;
				while ( true == $continue ) {
					$r = $this->client->read();
					if ( ! is_empty( $r ) ) {
						$res = call_user_func( $callback, $r );
						if ( true === $res || $false === $res ) {
							$continue = (bool) $res;
						}
					}
				}
				$this->client->close();
			}
			catch ( Exception $e ) {}
			return true;
		}
	}