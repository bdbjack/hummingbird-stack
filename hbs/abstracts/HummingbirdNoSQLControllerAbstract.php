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
					catch ( \Exception $e ) {
						$this->handleException( $e );
					}
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
						$this->handleException( $e );
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
								$results[ $obj->id ] = $obj;
							}
						}
					}
					catch ( \Exception $e ) {
						$this->handleException( $e );
					}
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
							$this->handleException( $e );
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
							$this->handleException( $e );
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
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$sids = array();
					if ( __hba_can_loop( $objects ) ) {
						$chunks = array_chunk( $objects, 500 );
						if ( __hba_can_loop( $chunks ) ) {
							foreach ( $chunks as $chunk ) {
								$nc = array_map( array( $this, 'convertRowToElasticSearchDoc' ), $chunk );
								$query = array(
									'body' => array(),
								);
								foreach ( $nc as $r ) {
									$ipush = array(
										'index' => array(
											'_index' => get_array_key( 'index', $r ),
											'_type' => get_array_key( 'type', $r ),
										),
									);
									if ( ! is_empty( get_array_key( 'id', $r ) ) ) {
										$ipush['index']['_id'] = get_array_key( 'id', $r );
									}
									$bpush = get_array_key( 'body', $r );
									array_push( $query['body'], $ipush );
									array_push( $query['body'], $bpush );
								}
								try {
									$response = $this->dbc->bulk( $query );
								}
								catch ( \Exception $e ) {
									$this->handleException( $e );
									$response = json_decode( $e->getMessage() );
								}
								if ( can_loop( get_array_key( 'items', $response, array() ) ) ) {
									foreach ( get_array_key( 'items', $response, array() ) as $item ) {
										$i = get_array_key( 'index', $item );
										$sids[ get_array_key( '_id', $i ) ] = get_array_key( 'status', $i );
									}
								}
								sleep( 1 );
							}
						}
					}
					return $sids;
					break;

				default:
					$sids = array();
					if ( __hba_can_loop( $objects ) ) {
						foreach ( $objects as $obj ) {
							$sid = $this->store( $obj );
							$sids[ $obj->id ] = $sid;
						}
					}
					return $sids;
					break;
			}
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
							$this->handleException( $e );
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
						$this->handleException( $e );
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

		protected function convertRowToElasticSearchDoc( $row ) {
			$doc = $row->asElasticsearchdoc();
			if ( is_empty( $row->id ) ) {
				unset( $doc['id'] );
			}
			return $doc;
		}

		protected static function camelcaseToUnderscore( $input ) {
			return strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $input ) );
		}

		protected function handleException( $e ) {
			$this->dbc->handleException( $e );
		}
	}