<?php
	namespace Hummingbird;

	class noSQLObject {
		private $_index = '';
		private $_type = '';
		private $_id = '';
		private $_body = array();

		function __construct( string $index, string $type, string $id = '' ) {
			$this->_index = $index;
			$this->_type = $type;
			$this->_id = $id;
		}

		function asArray() {
			$return = array(
				'index' => $this->_index,
				'type' => $this->_type,
				'id' => $this->_id,
			);
			if ( __hba_can_loop( $this->_body ) ) {
				foreach ( $this->_body as $key => $value ) {
					$return[ $key ] = $value;
				}
			}
			return $return;
		}

		function asObject() {
			$return = new \Hummingbird\noSQLDocument();
			$return->index = $this->_index;
			$return->type = $this->_type;
			$return->id = $this->_id;
			if ( __hba_can_loop( $this->_body ) ) {
				foreach ( $this->_body as $key => $value ) {
					$return->{$key} = $value;
				}
			}
			return $return;
		}

		function asJSON() {
			return json_encode( $this->asObject() );
		}

		function __toString() {
			return $this->_id;
		}

		function asRedbean( \Hummingbird\HummingbirdDatabaseControllerInterface $dbc ) {
			if ( ! is_empty( $this->_id ) && is_numeric( $this->_id ) ) {
				$bean = $dbc->load( $this->_type, $this->_id );
			}
			else if ( ! is_empty( $this->_id ) && ! is_numeric( $this->_id ) ) {
				throw new \Exception( "This database type requires numeric ID's", 1 );
			}
			else {
				$bean = $dbc->dispense( $this->_type );
			}
			if ( __hba_can_loop( $this->_body ) ) {
				foreach ( $this->_body as $key => $value ) {
					if ( is_object( $value ) || is_array( $value ) ) {
						$bean->{$key} = serialize( $value );
					}
					else {
						$bean->{$key} = $value;
					}
				}
			}
			return $bean;
		}

		function asElasticsearchdoc() {
			$doc = array(
				'index' => $this->_index,
				'type' => $this->_type,
				'id' => $this->_id,
				'body' => $this->_body,
			);
			return $doc;
		}

		public static function fromRedbean( \RedBeanPHP\OODBBean $bean, \Hummingbird\HummingbirdDatabaseControllerInterface $dbc ) {
			$c = get_called_class();
			$beanType = $bean->getMeta( 'type' );
			$type = self::removePrefixFromBeanType( $dbc->getParam( 'prefix' ), $beanType );
			$obj = new $c( $dbc->getParam( 'name' ), $type );
			$bp = $bean->export();
			if ( intval( __hba_get_array_key( 'id', $bp, 0 ) ) > 0 ) {
				$obj->id = intval( __hba_get_array_key( 'id', $bp, 0 ) );
			}
			if ( __hba_can_loop( $bp ) ) {
				foreach ( $bp as $key => $value ) {
					if ( 'id' !== $key ) {
						$obj->{$key} = $value;
					}
				}
			}
			return $obj;
		}

		public static function fromElasticsearchdoc( \stdClass $doc ) {
			$c = get_called_class();
			$obj = new $c( __hba_get_object_property( '_index', $doc, '' ), __hba_get_object_property( '_type', $doc, '' ) );
			if ( is_empty( $obj->index ) || is_empty( $obj->type ) ) {
				throw new \Exception( "Invalid Elasticsearch Document. Missing 'index' or 'type' properties.", 1 );
			}
			if ( ! is_empty( __hba_get_object_property( '_id', $doc, '' ) ) ) {
				if ( property_exists( $doc, 'found' ) && true == $doc->found ) {
					$obj->id = __hba_get_object_property( '_id', $doc, '' );
				}
				else if ( ! property_exists( $doc, 'found' ) ) {
					$obj->id = __hba_get_object_property( '_id', $doc, '' );
				}
			}
			foreach ( __hba_get_object_property( '_source', $doc, new \stdClass ) as $key => $value ) {
				$obj->{$key} = $value;
			}
			return $obj;
		}

		function __get( string $name ) {
			switch ( $name ) {
				case 'index':
					return $this->_index;
					break;

				case 'type':
					return $this->_type;
					break;

				case 'id':
					return $this->_id;
					break;

				default:
					return __hba_get_array_key( $name, $this->_body, null );
					break;
			}
		}

		function __set( string $name, $value ) {
			switch ( $name ) {
				case 'index':
					return false;
					break;

				case 'type':
					return false;
					break;

				case 'id':
					if ( __hba_is_empty( $this->_id ) ) {
						$this->_id = $value;
					}
					break;

				default:
					$this->_body[ $name ] = $value;
					break;
			}
		}

		function __isset( string $name ) {
			return ( in_array( $name, array( 'index', 'type', 'id' ) ) || array_key_exists( $name, $this->_body ) );
		}

		function __unset( string $name ) {
			if ( array_key_exists( $name, $this->_body ) ) {
				unset( $this->_body[ $name ] );
			}
		}

		function __call( string $name, array $arguments = array() ) {
			return false;
		}

		static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		private static function removePrefixFromBeanType( $prefix, $type ) {
			return substr( $type, strlen( $prefix ) );
		}
	}

	class noSQLDocument {
		function __get( string $name ) {
			return __hba_get_object_property( $name, $this, null );
		}

		function __set( string $name, $value ) {
			$this->{$name} = $value;
			return true;
		}
	}