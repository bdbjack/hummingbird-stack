<?php
	namespace Hummingbird;

	class HummingbirdPhoneInterface {
		public $valid = false;
		public $number = null;
		public $country = null;
		public $type = null;

		function __construct( string $number, string $iso = '' ) {
			$number = __hba_sanitize_phone( $number );
			if ( __hba_is_empty( $iso ) ) {
				$iso = __hba_guess_country_from_phone_number( $number );
			}
			$iso = strtoupper( $iso );
			$this->country = $iso;
			$util = self::_get_phone_util();
			$proto = self::_make_number_prototype( $number, $this->country );
			if ( is_a( $proto, '\libphonenumber\PhoneNumber' ) ) {
				$this->valid = $util->isValidNumber( $proto );
				if ( true == $this->valid ) {
					$this->number = $number;
				}
				$lt = $util->getNumberType( $proto );
				$this->type = self::_get_number_type_name( $lt );
			}
		}

		function format( string $format = 'noplus' ) {
			$util = self::_get_phone_util();
			$proto = self::_make_number_prototype( $this->number, $this->country );
			if ( is_a( $proto, '\libphonenumber\PhoneNumber' ) && true == $this->valid ) {
				switch ( strtolower( $format ) ) {
					case 'local':
						return $util->format( $proto, \libphonenumber\PhoneNumberFormat::NATIONAL );
						break;

					case 'international':
						return $util->format( $proto, \libphonenumber\PhoneNumberFormat::INTERNATIONAL );
						break;

					case 'e164':
						return $util->format( $proto, \libphonenumber\PhoneNumberFormat::E164 );
						break;

					case 'noplus':
						return substr( $util->format( $proto, \libphonenumber\PhoneNumberFormat::E164 ), 1 );
						break;
				}
			}
			return $this->number;
		}

		function getCarrier() {
			$cm = self::_get_carrier_mapper();
			$proto = self::_make_number_prototype( $this->number, $this->country );
			if ( is_a( $cm, 'libphonenumber\PhoneNumberToCarrierMapper' ) && is_a( $proto, '\libphonenumber\PhoneNumber' ) && true == $this->valid ) {
				return $cm->getNameForNumber( $proto, 'en' );
			}
			return '';
		}

		public static function isValid( string $number, string $iso ) {
			$util = self::_get_phone_util();
			$proto = self::_make_number_prototype( $number, $iso );
			if ( is_a( $proto, '\libphonenumber\PhoneNumber' ) ) {
				return $util->isValidNumber( $proto );
			}
			return false;
		}

		public static function isMobile( string $number, string $iso ) {
			if ( self::isValid( $number, $iso ) ) {
				$util = self::_get_phone_util();
				$proto = self::_make_number_prototype( $number, $iso );
				$lt = $util->getNumberType( $proto );
				return ( 2 == intval( $lt ) || 3 == intval( $lt ) );
			}
			return false;
		}

		private static function _get_phone_util() {
			if ( class_exists( '\libphonenumber\PhoneNumberUtil' ) ) {
				return \libphonenumber\PhoneNumberUtil::getInstance();
			}
			return false;
		}

		private static function _get_carrier_mapper() {
			if ( class_exists( 'libphonenumber\PhoneNumberToCarrierMapper' ) ) {
				return \libphonenumber\PhoneNumberToCarrierMapper::getInstance();
			}
			return false;
		}

		private static function _make_number_prototype( string $number, string $iso ) {
			$util = self::_get_phone_util();
			if ( is_a( $util, '\libphonenumber\PhoneNumberUtil' ) ) {
				try {
					return $util->parse( __hba_sanitize_phone( $number ), $iso );
				}
				catch ( \Exception $e ) {}
			}
			return false;
		}

		private static function _get_number_type_name( int $val = 1000 ) {
			$types = array(
				0 => 'LANDLINE',
				1 => 'MOBILE',
				2 => 'LANDLINE_OR_MOBILE',
				3 => 'TOLL_FREE',
				4 => 'PREMIUM_RATE',
				5 => 'SHARED_COST',
				6 => 'VOIP',
				7 => 'PERSONAL_NUMBER',
				8 => 'PAGER',
				9 => 'UAN',
				10 => 'UNKNOWN',
				27 => 'EMERGENCY',
				28 => 'VOICEMAIL',
				29 => 'SHORT_CODE',
				30 => 'STANDARD_RATE',
			);
			return __hba_get_array_key( $val, $types, 'UNKNOWN' );
		}
	}