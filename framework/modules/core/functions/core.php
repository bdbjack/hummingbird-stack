<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	function load404Page( $data ) {
		$fbc = HC::getFeedbackHandlerClass();
		switch ( $fbc ) {
			case 'Html_Feedback':
				$fbc::SUCCESS( array(
					'action' => '404',
				), 'Page Not Found', array(), 404, true );
				break;

			default:
				$fbc::FAILURE( null, 'Endpoint Not Found', array(
					'noSuchEndpoint',
					HC::getCurrentRelativePath( false ),
				), 404, true );
				break;
		}
	}

	function addDebugger( $data = null ) {
		if ( true == HC::getStaticConfigSetting( 'application', 'debug' ) ) {
			global $__hcc_obj;
			$__hcc_obj->addRoute( 'GET', '/debug/config/', 'getDebugConfig', false, false, 'Configuration Debug', true );
			$__hcc_obj->addRoute( 'POST', '/debug/config/', 'getDebugConfig', false, false, 'Configuration Debug', true );
			$__hcc_obj->addRoute( 'PUT', '/debug/config/', 'getDebugConfig', false, false, 'Configuration Debug', true );
			$__hcc_obj->addRoute( 'DELETE', '/debug/config/', 'getDebugConfig', false, false, 'Configuration Debug', true );
			$__hcc_obj->addRoute( 'CLI', '/debug/config/', 'getDebugConfig', false, false, 'Configuration Debug', true );
		}
	}

	function loadDebugConfig( $data ) {
		global $__hcc_obj;
		if ( true !== HC::getStaticConfigSetting( 'application', 'debug' ) ) {
			load404Page( $data );
		}
		$fbc = HC::getFeedbackHandlerClass();
		switch ( $fbc ) {
			case 'Html_Feedback':
				$fbc::SUCCESS( array(
					'action' => 'debugtpl',
				), 'Sample PHP Configuration File', array(), 200, true );
				break;

			default:
				$fbc::DEBUG( $__hcc_obj->getConfigPHP(), 'Configuration File Example', array(), 200, true );
				break;
		}
	}