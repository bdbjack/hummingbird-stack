<?php
	namespace Hummingbird;

	interface HummingbirdEmailControllerInterface {
		public function __construct( \Hummingbird\HummingbirdApp $hba );
		public function send( string $subject = '', string $body = '', string $altBody = '', string $sender = '', string $senderName = '', array $recipients = array(), array $cc = array(), $bcc = array(), array $attachments = array() );

	}