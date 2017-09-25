<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	$this->addAction( 'initRouting', 'addDebugger', 0 );
	$this->addAction( 'route_action_404', 'load404Page' );
	$this->addAction( 'route_action_getDebugConfig', 'loadDebugConfig' );