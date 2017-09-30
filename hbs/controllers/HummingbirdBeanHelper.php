<?php
	namespace Hummingbird;

	class HummingbirdBeanHelper extends \RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper {
		public function getModelForBean( \RedBeanPHP\OODBBean $bean ) {
			$type = $bean->getMeta( 'type' );
			$dbc = $this->getCallingHummingbirdDatabaseController();
			if ( __hba_is_instance_of( $dbc, 'Hummingbird\HummingbirdDatabaseControllerInterface' ) ) {
				$original_bean_name = str_replace( $dbc->getDBPrefix(), '', $type );
			}
			else {
				$original_bean_name = $type;
			}
			$model_name = sprintf( '%s%s', ucfirst( 'model_' ), ucfirst( strtolower( $original_bean_name ) ) );
			if ( defined( 'REDBEAN_MODEL_PREFIX' ) ) {
				$full_class_name = sprintf( '%s%s', REDBEAN_MODEL_PREFIX, $model_name );
			}
			else {
				$full_class_name = $model_name;
			}
			if ( class_exists( $full_class_name ) ) {
				$return = new $full_class_name;
			}
			else {
				return null;
			}
			$return->loadBean( $bean );
			return $return;
		}

		private function getCallingHummingbirdDatabaseController() {
			$return = false;
			$bt = debug_backtrace();
			foreach ( $bt as $trace ) {
				if ( __hba_is_instance_of( __hba_get_array_key( 'object', $trace ), 'Hummingbird\HummingbirdDatabaseControllerInterface' ) ) {
					return __hba_get_array_key( 'object', $trace );
				}
			}
			return $return;
		}
	}