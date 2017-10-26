<?php
	namespace Hummingbird;

	abstract class HummingbirdNoSQLControllerAbstract implements \Hummingbird\HummingbirdNoSQLControllerInterface {
		protected $dbc;

		function __construct( \Hummingbird\HummingbirdDatabaseControllerInterface $dbc ) {
			$this->dbc = $dbc;
		}

		function dispense( string $type ) {
			return $this->getNoSQLObject( $this->dbc->getParam( 'name' ), $type );
		}

		function count( string $type, $query, array $bindings = array() ) {
			$results = 0;
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					if ( ! is_array( $query ) || ! __hba_can_loop( $query ) ) {
						$query = array(
							'query' => array(
								'match_all' => new \stdClass(),
							),
						);
					}
					$params = array(
						'index' => $this->dbc->getParam( 'name' ),
						'type' => $type,
						'body' => $query,
					);
					try {
						$rawRes = $this->dbc->count( $params );
						$results = intval( __hba_get_array_key( 'count', $rawRes, array() ) );
					}
					catch ( \Exception $e ) {}
					break;

				default:
					$results = intval( $this->dbc->count( $type, $query, $bindings ) );
					break;
			}
			return $results;
		}

		function load( string $type, string $id ) {
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					try {
						$doc = $this->dbc->get( array(
							'index' => $this->dbc->getParam( 'name' ),
							'type' => $type,
							'id' => $id,
						) );
					}
					catch ( \Exception $e ) {
						$doc = json_decode( $e->getMessage() );
					}
					if ( ! is_object( $doc ) ) {
						$this->makeElasticsearchDocAsObject( $doc );
					}
					$ret = \Hummingbird\noSQLObject::fromElasticsearchdoc( $doc );
					break;

				default:
					$bean = $this->dbc->load( $type, $id );
					$ret = \Hummingbird\noSQLObject::fromRedbean( $bean, $this->dbc );
					break;
			}
			return $ret;
		}

		function loadAll( string $type, array $ids ) {
			$return = array();
			if ( __hba_can_loop( $ids ) ) {
				foreach ( $ids as $id ) {
					$return[ $id ] = $this->load( $type, $id );
				}
			}
			return $return;
		}

		function find( string $type, $query, array $bindings = array() ) {
			$results = array();
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					if ( ! is_array( $query ) || ! __hba_can_loop( $query ) ) {
						$query = array(
							'query' => array(
								'match_all' => new \stdClass(),
							),
						);
					}
					$params = array(
						'index' => $this->dbc->getParam( 'name' ),
						'type' => $type,
						'body' => $query,
					);
					try {
						$rawRes = $this->dbc->search( $params );
						$hits = __hba_get_array_key( 'hits', __hba_get_array_key( 'hits', $rawRes, array() ), array() );
						if ( __hba_can_loop( $hits ) ) {
							foreach ( $hits as $doc ) {
								if ( ! is_object( $doc ) ) {
									$nd = $this->makeElasticsearchDocAsObject( $doc );
									$obj = \Hummingbird\noSQLObject::fromElasticsearchdoc( $nd );
								}
								else {
									$obj = \Hummingbird\noSQLObject::fromElasticsearchdoc( $doc );
								}
							}
							$results[ $obj->id ] = $obj;
						}
					}
					catch ( \Exception $e ) {}
					break;

				default:
					$rawRes = $this->dbc->find( $type, $query, $bindings );
					if ( __hba_can_loop( $rawRes ) ) {
						foreach ( $rawRes as $id => $bean ) {
							$results[ $id ] = \Hummingbird\noSQLObject::fromRedbean( $bean, $this->dbc );
						}
					}
					break;
			}
			return $results;
		}

		function findOne( string $type, $query, array $bindings = array() ) {
			$all = $this->find( $type, $query, $bindings );
			if ( __hba_can_loop( $all ) ) {
				$objs = array_values( $all );
				return array_shift( $objs );
			}
			return false;
		}

		function store( \Hummingbird\noSQLObject &$object ) {
			$sid = null;
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$params = $object->asElasticsearchdoc();
					if ( __hba_is_empty( $object->id ) ) {
						try {
							unset( $params['id'] );
							$response = $this->dbc->index( $params );
						}
						catch ( \Exception $e ) {
							$response = json_decode( $e->getMessage() );
						}
					}
					else {
						try {
							$params['body'] = array(
								'doc' => $params['body'],
							);
							$response = $this->dbc->update( $params );
						}
						catch ( \Exception $e ) {
							$response = json_decode( $e->getMessage() );
						}
					}
					$sid = __hba_get_array_key( '_id', $response );
					if ( true == __hba_get_array_key( 'created', $response ) ) {
						$object->id = $sid;
					}
					break;

				default:
					$bean = $object->asRedbean( $this->dbc );
					$response = $this->dbc->store( $bean );
					if ( intval( $response ) > 0 ) {
						$object->id = intval( $response );
						$sid = intval( $response );
					}
					break;
			}
			return $sid;
		}

		function storeAll( &$objects ) {
			$sids = array();
			if ( __hba_can_loop( $objects ) ) {
				foreach ( $objects as $obj ) {
					$sid = $this->store( $obj );
					$sids[ $obj->id ] = $sid;
				}
			}
			return $sids;
		}

		function trash( \Hummingbird\noSQLObject &$object ) {
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$params = $object->asElasticsearchdoc();
					if ( __hba_is_empty( $object->id ) ) {
						$response = true;
					}
					else {
						try {
							unset( $params['body'] );
							$response = $this->dbc->delete( $params );
						}
						catch ( \Exception $e ) {
							$response = json_decode( $e->getMessage() );
						}
						$response = ( 'deleted' == __hba_get_array_key( 'result', $response ) );
					}
					break;

				default:
					$bean = $object->asRedbean( $this->dbc );
					$this->dbc->trash( $bean );
					$response = true;
					break;
			}
			$object = null;
			return $response;
		}

		function trashAll( array &$objects ) {
			$return = array();
			if ( __hba_can_loop( $objects ) ) {
				foreach ( $objects as $index => $obj ) {
					$return[ $obj->id ] = $this->trash( $obj );
					$objects[ $index ] = $obj;
				}
			}
			return $return;
		}

		function wipe( $type ) {
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$return = false;
					break;

				default:
					$return = $this->dbc->wipe( $type );
					break;
			}
			return $return;
		}

		function nuke() {
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$params = array(
						'index' => $this->dbc->getParam( 'name' ),
					);
					try {
						$res = $this->dbc->indices()->delete( $params );
					}
					catch ( \Exception $e ) {
						$res = json_decode( $e->getMessage() );
					}
					$return = ( true == __hba_get_array_key( 'acknowledged', $res ) );
					break;

				default:
					$return = $this->dbc->nuke();
					break;
			}
			return $return;
		}

		protected function getNoSQLObject( $index, $type, $id = '' ) {
			return new \Hummingbird\noSQLObject( $index, $type, $id );
		}

		protected function makeElasticsearchDocAsObject( array &$doc ) {
			$doc = json_decode( json_encode( $doc ) );
			return $doc;
		}

		protected static function camelcaseToUnderscore( $input ) {
			return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
		}
	}