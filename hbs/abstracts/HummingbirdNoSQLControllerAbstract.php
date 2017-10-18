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

		function store( \Hummingbird\noSQLObject $object ) {

		}

		function storeAll( $objects ) {

		}

		function trash( \Hummingbird\noSQLObject $object ) {

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