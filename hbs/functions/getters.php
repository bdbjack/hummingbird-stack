<?php
	function __hba_get_array_key( $key, $array = array(), $default = null ) {
		return ( is_array( $array ) && array_key_exists( $key, $array ) ) ? $array[ $key ] : $default;
	}

	function __hba_get_defined_value( $key, $default = null ) {
		return ( defined( $key ) && ! is_empty( constant( $key ) ) ) ? constant( $key ) : $default;
	}

	function __hba_get_object_property( $key, $obj, $default = null ) {
		return ( is_object( $obj ) && property_exists( $obj, $key ) ) ? $obj->{$key} : $default;
	}