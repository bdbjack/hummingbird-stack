<?php
	namespace Hummingbird;

	abstract class HummingbirdEmailControllerAbstract implements \Hummingbird\HummingbirdEmailControllerInterface {
		protected $hba;
		protected $mailer;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
			$this->mailer = new \PHPMailer\PHPMailer\PHPMailer();
			$this->mailer->isSMTP();
			$this->mailer->isHTML( true );
			$this->mailer->Host = $this->hba->getConfigSetting( 'smtp', 'host' );
			$this->mailer->Port = __hba_sanitize_absint( $this->hba->getConfigSetting( 'smtp', 'port' ) );
			if ( true == $this->hba->getConfigSetting( 'smtp', 'auth' ) ) {
				$this->mailer->SMTPAuth = true;
				$this->mailer->Username = $this->hba->getConfigSetting( 'smtp', 'user' );
				$this->mailer->Password = $this->hba->getConfigSetting( 'smtp', 'pass' );
			}
			if ( ! __hba_is_empty( $this->hba->getConfigSetting( 'smtp', 'encrypt' ) ) ) {
				$this->mailer->SMTPSecure = $this->hba->getConfigSetting( 'smtp', 'encrypt' );
			}
			$this->mailer->SMTPKeepAlive = true;
			$this->mailer = $this->hba->doFilter( 'phpmailer_init', $this->mailer );
			if ( true == $hba->getConfigSetting( 'application', 'debug' ) ) {
				$this->mailer->SMTPDebug = 2;
				$this->mailer->Debugoutput = 'error_log';
			}
		}

		function send( string $subject = '', string $body = '', string $altBody = '', string $sender = '', string $senderName = '', array $recipients = array(), array $cc = array(), $bcc = array(), array $attachments = array() ) {
			if ( __hba_can_loop( $this->hba->getConfigSetting( 'smtp', 'senders' ) ) ) {
				if ( ! in_array( $sender, $this->hba->getConfigSetting( 'smtp', 'senders' ) ) ) {
					return false;
				}
			}
			$mailer = clone $this->mailer;
			$mailer->setFrom( $sender, $senderName );
			$mailer->Subject = $subject;
			$mailer->Body = $body;
			$mailer->AltBody = $altBody;
			if ( __hba_can_loop( $recipients ) ) {
				foreach ( $recipients as $name => $address ) {
					if ( ! is_numeric( $name ) ) {
						$mailer->addAddress( $address, $name );
					}
					else {
						$mailer->addAddress( $address );
					}
				}
			}
			if ( __hba_can_loop( $cc ) ) {
				foreach ( $cc as $name => $address ) {
					if ( ! is_numeric( $name ) ) {
						$mailer->addCC( $address, $name );
					}
					else {
						$mailer->addCC( $address );
					}
				}
			}
			if ( __hba_can_loop( $bcc ) ) {
				foreach ( $bcc as $name => $address ) {
					if ( ! is_numeric( $name ) ) {
						$mailer->addBCC( $address, $name );
					}
					else {
						$mailer->addBCC( $address );
					}
				}
			}
			if ( __hba_can_loop( $attachments ) ) {
				foreach ( $attachments as $name => $address ) {
					if ( ! is_numeric( $name ) ) {
						$mailer->addAttachment( $address, $name );
					}
					else {
						$mailer->addAttachment( $address );
					}
				}
			}
			$send = $mailer->send();
			return $send;
		}

		function __get( string $name ) {
			return null;
		}

		function __set( string $name, $value ) {
			return false;
		}

		function __isset( string $name ) {
			return false;
		}

		function __unset( string $name ) {
			return false;
		}

		function __call( string $name, array $arguments = array() ) {
			return false;
		}

		static function __callStatic( string $name, array $arguments = array() ) {
			return false;
		}

		function sendDebugOutputToLog( $str, $level ) {
			$this->hba->runErrorFunction( 'writeToLogFile', sprintf( 'Email Debug Level %d Message: %s', $level, $str ) );
			$this->hba->runErrorFunction( 'reportToNewRelic', sprintf( 'Email Debug Level %d Message: %s', $level, $str ) );
		}
	}