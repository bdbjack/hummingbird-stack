<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function init_redbean() {
		global $__hcc_obj;
		$databases = __redbean_get_app_databases();
		foreach ( $databases as $key => $dbi ) {
			if ( 'default' == $key ) {
				try {
					$res = R::setup( HC::getArrayKey( 'upn', $dbi, 'sqlite:/tmp/tmp.db' ), HC::getArrayKey( 'user', $dbi ), HC::getArrayKey( 'pass', $dbi ), ( true == HC::getArrayKey( 'frozen', $dbi, false ) ) );
					if ( is_a( $res, 'RedBeanPHP\ToolBox' ) ) {
						$__hcc_obj->addActiveDb( 'default', HC::getArrayKey( 'prefix', $dbi, '' ) );
					}
				}
				catch ( Exception $e ) {
					trigger_error( sprintf( 'Could not initiate default database: %s', $e->getMessage() ) );
				}
			}
			else {

			}
		}
		//HC::debug( $databases );
	}

	function __redbean_get_app_databases() {
		$dbs = HC::getConfigSection( 'databases' );
		if ( ! HC::canLoop( $dbs ) ) {
			$dbs = array(
				'default' => array(
					'type' => 'sqlite',
					'host' => '',
					'port' => 0,
					'name' => '/tmp/dbfile.db',
					'user' => null,
					'pass' => null,
					'prefix' => null,
					'frozen' => false,
				)
			);
		}
		$return = array();
		foreach ( $dbs as $key => $dbd ) {
			switch ( HC::getArrayKey( 'type', $dbd, 'sqlite' ) ) {
				case 'sqlite':
					$upn = sprintf(
						'%s:%s',
						HC::getArrayKey( 'type', $dbd, 'sqllite' ),
						HC::getArrayKey( 'name', $dbd, '/tmp/dbfile.db' )
					);
					break;

				default:
					$upn = sprintf(
						'%s:host=%s;port=%d;dbname=%s',
						HC::getArrayKey( 'type', $dbd, 'sqllite' ),
						HC::getArrayKey( 'host', $dbd, 'localhost' ),
						HC::getArrayKey( 'port', $dbd, '3306' ),
						HC::getArrayKey( 'name', $dbd, '' )
					);
					break;
			}
			$return[ $key ] = array(
				'upn' => $upn,
				'user' => HC::getArrayKey( 'user', $dbd, '' ),
				'pass' => HC::getArrayKey( 'pass', $dbd, '' ),
				'prefix' => HC::getArrayKey( 'prefix', $dbd, '' ),
				'frozen' => HC::getArrayKey( 'frozen', $dbd, false ),
			);
		}
		return $return;
	}

	function db_dispense( $type, $param = null ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! is_empty( $param ) ) {
			return R::getRedBean()->dispense( $ot, $param );
		}
		return R::getRedBean()->dispense( $ot );
	}

	function db_dispense_all( $type, $param = null ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! is_empty( $param ) ) {
			return R::getRedBean()->dispenseAll( $ot, $param );
		}
		return R::getRedBean()->dispenseAll( $ot );
	}

	function db_load( $type, $id = 0 ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->load( $ot, $id );
	}

	function db_load_all( $type, $ids = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->loadAll( $ot, $ids );
	}

	function db_find( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->find( $ot, array(), $query, $vars );
	}

	function db_find_one( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		$res = R::sysFind( $type, $query, $vars );
		if ( ! can_loop( $res ) ) {
			return null;
		}
		$reskeys = array_keys( $res );
		return $res[ $reskeys[0] ];
	}

	function db_find_all( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->find( $ot, array(), $query, $vars );
	}

	function db_count( $type, $query = null, $vars = array() ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		if ( ! can_loop( $vars ) ) {
			return R::getRedBean()->count( $ot, $query );
		}
		return R::getRedBean()->count( $ot, $query, $vars );
	}

	function db_wipe( $type ) {
		$ot = sprintf( '%s%s', get_db_pref(), $type );
		return R::getRedBean()->wipe( $ot );
	}

	function make_sql_concatted_list( array $array = array() ) {
		$nl = array();
		if ( can_loop( $array ) ) {
			foreach ( $array as $val ) {
				array_push( $nl, sprintf( "'%s'", $val ) );
			}
		}
		return implode( ',', $nl );
	}