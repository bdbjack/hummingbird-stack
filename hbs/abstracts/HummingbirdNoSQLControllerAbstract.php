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

		function load( string $type, string $id ) {
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					try {
						$doc = json_decode( $this->dbc->get( array(
							'index' => $this->dbc->getParam( 'name' ),
							'type' => $type,
							'id' => $id,
						) ) );
					}
					catch ( \Exception $e ) {
						$doc = json_decode( $e->getMessage() );
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
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					
					break;

				default:
					$beans = $this->dbc->loadAll( $type, $ids );

					//$ret = \Hummingbird\noSQLObject::fromRedbean( $bean, $this->dbc );
					break;
			}
			return $ret;
		}

		function find( $type, $query ) {

		}

		function findOne( $type, $query ) {

		}

		function store( \Hummingbird\noSQLObject &$object ) {
			$sid = null;
			switch ( $this->dbc->getParam( 'type' ) ) {
				case 'elasticsearch':
					$params = $object->asElasticsearchdoc();
					try {
						unset( $params['id'] );
						$response = $this->dbc->index( $params );
					}
					catch ( \Exception $e ) {
						$response = json_decode( $e->getMessage() );
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

		}

		function trash( \Hummingbird\noSQLObject &$object ) {

		}

		function trashAll( $objects ) {

		}

		function wipe( $type ) {

		}

		function nuke() {

		}

		protected function getNoSQLObject( $index, $type, $id = '' ) {
			return new \Hummingbird\noSQLObject( $index, $type, $id );
		}
	}