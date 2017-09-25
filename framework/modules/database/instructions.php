<?php
	defined( 'ABSPATH' ) || die( 'Sorry, but you cannot access this page directly.' );

	$this->addAction( 'initDatabases', 'init_redbean', 1000 );