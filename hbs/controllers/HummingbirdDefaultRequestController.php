<?php
	namespace Hummingbird;

	class HummingbirdDefaultRequestController implements \Hummingbird\HummingbirdRequestControllerInterface {
		private $hba;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
		}
	}