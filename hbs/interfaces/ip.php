<?php
	namespace Hummingbird;

	class HummingbirdIPInterface {
		private $_properties = array(
			'valid' => false,
			'address' => null,
			'cidr' => null,
			'IPv4' => false,
			'IPv6' => false,
			'private' => false,
			'reserved' => false,
		);

		private $_geo = array(
			'valid' => false,
			'continent' => null,
			'country' => null,
			'region' => null,
			'timezone' => null,
		);

		function __construct( string $ip = null ) {
			if ( __hba_is_empty( $ip ) ) {
				$ip = $this->_get_current_user_ip();
			}
			$this->_properties['address'] = trim( filter_var( $ip, FILTER_VALIDATE_IP ) );
			$this->_properties['valid'] = ( ! __hba_is_empty( $this->_properties['address'] ) );
			$this->_properties['IPv4'] = ( true == filter_var( $this->_properties['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) );
			$this->_properties['IPv6'] = ( true == filter_var( $this->_properties['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) );
			$this->_properties['private'] = ( false == filter_var( $this->_properties['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE ) );
			$this->_properties['reserved'] = ( true == filter_var( $this->_properties['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE ) );
			$this->_properties['cidr'] = sprintf( '%s/%s', $this->_properties['address'], ( true == $this->_properties['IPv4'] ) ? '32' : '128' );
			if ( true == $this->_properties['valid'] && function_exists( 'geoip_db_avail' ) && geoip_db_avail( GEOIP_COUNTRY_EDITION ) ) {
				$this->_geo['continent'] = geoip_continent_code_by_name( $this->_properties['address'] );
				$this->_geo['country'] = geoip_country_code_by_name( $this->_properties['address'] );
				if ( true == geoip_db_avail( GEOIP_REGION_EDITION_REV0 ) || true == geoip_db_avail( GEOIP_REGION_EDITION_REV1 ) ) {
					$this->_geo['region'] = geoip_region_by_name( $this->_properties['address'] );
					$this->_geo['timezone'] = geoip_time_zone_by_country_and_region( $this->_geo['country'], $this->_geo['region'] );
				}
				else {
					$this->_geo['region'] = null;
					if ( ! __hba_is_empty( $this->_geo['country'] ) ) {
						$this->_geo['timezone'] = geoip_time_zone_by_country_and_region( $this->_geo['country'] );
					}
				}
				$this->_geo['valid'] = ( ! __hba_is_empty( $this->_geo['continent'] ) );
			}
		}

		function get( string $name = '' ) {
			$ro = new \stdClass();
			if ( array_key_exists( $name, $this->_properties ) ) {
				return __hba_get_array_key( $name, $this->_properties );
			}
			foreach ( $this->_properties as $key => $value ) {
				$ro->{$key} = $value;
			}
			return $ro;
		}

		function getGeo( string $name = '' ) {
			$ro = new \stdClass();
			if ( array_key_exists( $name, $this->_geo ) ) {
				return __hba_get_array_key( $name, $this->_geo );
			}
			foreach ( $this->_geo as $key => $value ) {
				$ro->{$key} = $value;
			}
			return $ro;
		}

		function ip() {
			return $this->get( 'address' );
		}

		function asArray() {
			$return = array();
			if ( __hba_can_loop( $this->_properties ) ) {
				foreach ( $this->_properties as $key => $value ) {
					$return[ $key ] = $value;
				}
			}
			if ( __hba_can_loop( $this->_geo ) ) {
				foreach ( $$this->_geo as $key => $value ) {
					$return[ $key ] = $value;
				}
			}
			return $return;
		}

		function asObject() {
			$arr = $this->asArray();
			$obj = new \stdClass();
			if ( __hba_can_loop( $arr ) ) {
				foreach ( $arr as $key => $value ) {
					$obj->{$key} = $value;
				}
			}
			return $obj;
		}

		public function __get( string $name ) {
			if ( 'ip' == $name ) {
				$name = 'address';
			}
			return __hba_get_array_key( $name, $this->_properties );
		}

		public function __set( string $name, $value ) {
			return false;
		}

		public function __isset( string $name ) {
			return false;
		}

		public function __unset( string $name ) {
			return false;
		}

		public function __call( string $name, array $arguments = array() ) {
			return false;
		}

		public static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		public function __toString() {
			return $this->ip();
		}

		private function _get_current_user_ip() {
			switch ( true ) {
				case ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ):
					$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
					break;

				case ( isset( $_SERVER['HTTP_INCAP_CLIENT_IP'] ) ):
					$ip = $_SERVER['HTTP_INCAP_CLIENT_IP'];
					break;

				case ( isset( $_SERVER['True-Client-IP'] ) ):
					$ip = $_SERVER['True-Client-IP'];
					break;

				case ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ):
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					break;

				case ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ):
					$ip = $_SERVER['HTTP_X_REAL_IP'];
					break;

				case ( isset( $_SERVER['X-Forwarded-For'] ) ):
					$ip = $_SERVER['X-Forwarded-For'];
					break;

				case ( isset( $_SERVER['X-Forwarded-For'] ) ):
					$ip = $_SERVER['X-Forwarded-For'];
					break;

				case ( __hba_is_cli() ):
					$ip = '127.0.0.1';
					break;

				default:
					$cur = $_SERVER['REMOTE_ADDR'];
					$list = explode( ',', $cur );
					$real = filter_var( $list[0], FILTER_VALIDATE_IP );
					$parts = explode( '.', $real );
					$ip = $real;
					break;
				}
			return $ip;
		}

		public static function is_valid_cidr( $cidr, $must_cidr = false ) {
			if ( ! preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?$/", $cidr ) ) {
				$return = false;
			} else {
				$return = true;
			}
			if ( $return == true ) {
				$parts = explode("/", $cidr);
				if ( count( $parts ) <= 1 ) {
					return false;
				}
				$ip = $parts[0];
				$netmask = $parts[1];
				$octets = explode(".", $ip);
				foreach ( $octets as $octet ) {
					if ( $octet > 255) {
						$return = false;
					}
				}
				if ( ( ( $netmask != '' ) && ( $netmask > 32 ) && ! $must_cidr ) || ( ( $netmask == '' || $netmask > 32 ) && $must_cidr ) ) {
					$return = false;
				}
			}
			return $return;
		}

		public static function in_cidr( $ip = null, $range = null ) {
			if ( false == self::is_valid_cidr( $range ) ) {
				return false;
			}
			list ( $subnet, $bits ) = explode( '/', $range );
			$ip = ip2long( $ip );
			$subnet = ip2long( $subnet );
			$mask = -1 << ( 32 - $bits );
			$subnet &= $mask;
			return ( $ip & $mask ) == $subnet;
		}
	}