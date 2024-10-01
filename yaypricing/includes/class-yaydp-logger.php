<?php
/**
 * The Logger class is responsible for logging messages to a file
 * This class is useful for debugging and troubleshooting applications, as well as for monitoring and analyzing application performance
 *
 * @package YayPricing\Logger
 */

namespace YAYDP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare class
 */
class YAYDP_Logger {

	/**
	 * Logs the exception message to a file for debugging purposes
	 *
	 * @param \Exception $ex Current exception.
	 * @param bool       $is_ajax Is ajax request.
	 */
	public static function log_exception_message( $ex, $is_ajax = false ) {
		$message  = __( 'SYSTEM ERROR:', 'yaypricing' ) . $ex->getCode() . ' : ' . $ex->getMessage();
		$message .= PHP_EOL . $ex->getFile() . '(' . $ex->getLine() . ')';
		$message .= PHP_EOL . $ex->getTraceAsString();
		self::log( $message );
		if ( $is_ajax ) {
			wp_send_json_error( array( 'mess' => $message ) );
		}
	}

	/**
	 * Logs a message to a file. The file path and message are passed as parameters.
	 * If the file does not exist, it will be created.
	 * If the file exists, the message will be appended to the end of the file
	 *
	 * @param string $content Given message.
	 */
	public static function log( $content ) {
		$name     = __( 'log-', 'yaypricing' ) . current_time( 'timestamp' );
		$folder   = \YAYDP_PLUGIN_PATH . '/includes/Logs';
		$filename = $folder . DIRECTORY_SEPARATOR . $name . '.xml';
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$wp_filesystem->put_contents( $filename, $content );
	}
}
