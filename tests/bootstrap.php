<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package AS_PHP_Checkup
 * @subpackage Tests
 * @since 1.1.0
 */

// Determine the tests directory
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit parameters
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 *
 * @return void
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/as-php-checkup.php';
}

// Hook plugin loading
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Include test case classes
require_once dirname( __FILE__ ) . '/test-checkup.php';

echo "AS PHP Checkup Test Suite Loaded\n";
echo "Plugin Version: " . AS_PHP_CHECKUP_VERSION . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";