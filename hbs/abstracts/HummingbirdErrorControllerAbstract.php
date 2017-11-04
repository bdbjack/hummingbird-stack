<?php
	namespace Hummingbird;

	abstract class HummingbirdErrorControllerAbstract implements \Hummingbird\HummingbirdErrorControllerInterface {
		protected $hba;
		protected $errlog = '';
		protected $maxMsgLength = 0;

		function __construct( \Hummingbird\HummingbirdApp $hba ) {
			$this->hba = $hba;
			$this->errlog = getenv( 'error_log' );
			if ( __hba_is_empty( $this->errlog ) ) {
				$serverSoftware = getenv( 'SERVER_SOFTWARE' );
				if ( __hba_beginning_matches( 'Apache', $serverSoftware ) ) {
					$this->errlog = sprintf( '%s/error.log', getenv( 'APACHE_LOG_DIR' ) );
				}
				if ( __hba_beginning_matches( 'nginx', $serverSoftware ) ) {
					$this->errlog = sprintf( '%s/error.log', getenv( 'NGINX_LOG_DIR' ) );
				}
			}
			$this->maxMsgLength = intval( ini_get( 'log_errors_max_len' ) );
			ini_set( 'log_errors', 0 );
		}

		function handleError( int $errno, string $errstr, string $errfile = '', int $errline = 0 ) {
			$msg = sprintf( '%s: %s IN FILE %s ON LINE %d', ( $this->isCriticalError( $errno ) ) ? 'Critical Error' : 'Error', $errstr, $errfile, $errline );
			$this->writeToLogFile( $msg );
			$this->reportToNewRelic( $msg );
			if ( true == $this->isCriticalError( $errno ) || $this->hba->getConfigSetting( 'application', 'debug' ) ) {
				$this->showFeedback( $msg );
			}
			return $this->isCriticalError( $errno );
		}

		function handleException( \Exception $ex ) {
			if ( ( version_compare( phpversion(), '7.0.0', '>' ) ) ) {
				return $this->handlePHP7Exception( $ex );
			}
			$msg = sprintf( 'Exception: %s', $ex->getMessage() );
			$this->writeToLogFile( $msg );
			$this->reportToNewRelic( $msg );
			$this->showFeedback( $msg );
			return true;
		}

		function handlePHP7Exception( \Throwable $ex ) {
			$msg = sprintf( 'Exception: %s', $ex->getMessage() );
			$this->writeToLogFile( $msg );
			$this->reportToNewRelic( $msg );
			$this->showFeedback( $msg );
			return true;
		}

		function getExceptionHandlerFunctionName() {
			return ( version_compare( phpversion(), '7.0.0', '<' ) ) ? 'handleException' : 'handlePHP7Exception';
		}

		function setLogFile( string $file ) {
			clearstatcache( true, $file );
			if ( file_exists( $file ) && is_writable( $file ) ) {
				$this->errlog = $file;
			}
		}

		function writeToLogFile( string $msg = '' ) {
			if ( ( __hba_is_empty( $this->errlog ) || ! is_writable( $this->errlog ) ) && __hba_is_cli() ) {
				echo sprintf( '[%s] %s', date( 'Y-m-d H:i:s' ), $msg ) . "\r\n";
				return true;
			}
			else {
				$chunks = str_split( $msg, $this->maxMsgLength );
				if ( __hba_can_loop( $chunks ) ) {
					foreach ( $chunks as $chunk ) {
						return error_log( $chunk, 0, $this->errlog );
					}
				}
			}
			return false;
		}

		function reportToNewRelic( string $message, $exception = null ) {
			if ( extension_loaded( 'newrelic' ) && true == $this->hba->getConfigSetting( 'newrelic', 'enabled' ) ) {
				newrelic_notice_error( $message, $exception );
			}
		}

		function showFeedback( $msg = '' ) {
			$trace = debug_backtrace();
			array_shift( $trace );
			array_shift( $trace );
			$this->hba->runFeedbackFunction( 'failure', null, $msg, $trace, 400 );
			$this->hba->runFeedbackFunction( 'outputFeedback' );
		}

		function throwException( $msg ) {
			throw new \Exception( $msg, 1 );
		}


		private function isCriticalError( int $errcode ) {
			return ( in_array( $errcode, array( E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_STRICT, E_RECOVERABLE_ERROR ) ) );
		}
	}