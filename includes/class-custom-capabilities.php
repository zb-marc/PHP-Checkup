<?php
/**
 * Custom Capabilities Handler
 *
 * @package AS_PHP_Checkup
 * @since 1.2.1
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS_PHP_Checkup_Capabilities class
 *
 * @since 1.2.1
 */
class AS_PHP_Checkup_Capabilities {

	/**
	 * Instance of this class
	 *
	 * @since 1.2.1
	 * @var AS_PHP_Checkup_Capabilities|null
	 */
	private static $instance = null;

	/**
	 * Plugin capabilities
	 *
	 * @since 1.2.1
	 * @var array
	 */
	private $capabilities = array(
		'as_php_checkup_view'          => 'View PHP Checkup',
		'as_php_checkup_analyze'       => 'Analyze Plugins',
		'as_php_checkup_export'        => 'Export Reports',
		'as_php_checkup_apply_fixes'   => 'Apply Configuration Fixes',
		'as_php_checkup_manage_backups'=> 'Manage Backup Files',
		'as_php_checkup_view_logs'     => 'View Audit Logs',
	);

	/**
	 * Role capability mapping
	 *
	 * @since 1.2.1
	 * @var array
	 */
	private $role_caps = array(
		'administrator' => array(
			'as_php_checkup_view'          => true,
			'as_php_checkup_analyze'       => true,
			'as_php_checkup_export'        => true,
			'as_php_checkup_apply_fixes'   => true,
			'as_php_checkup_manage_backups'=> true,
			'as_php_checkup_view_logs'     => true,
		),
		'editor' => array(
			'as_php_checkup_view'   => true,
			'as_php_checkup_export' => true,
		),
		'author' => array(
			'as_php_checkup_view' => false,
		),
	);

	/**
	 * Constructor
	 *
	 * @since 1.2.1
	 */
	private function __construct() {
		// Hook into activation/deactivation
		register_activation_hook( AS_PHP_CHECKUP_PLUGIN_FILE, array( $this, 'add_capabilities' ) );
		register_deactivation_hook( AS_PHP_CHECKUP_PLUGIN_FILE, array( $this, 'remove_capabilities' ) );
		
		// Add filter for capability checks
		add_filter( 'user_has_cap', array( $this, 'filter_user_capabilities' ), 10, 4 );
		
		// Add admin menu for capability management
		add_action( 'admin_menu', array( $this, 'add_capabilities_menu' ), 99 );
	}

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.1
	 * @return AS_PHP_Checkup_Capabilities
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Add capabilities to roles
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function add_capabilities() {
		foreach ( $this->role_caps as $role_name => $caps ) {
			$role = get_role( $role_name );
			
			if ( null === $role ) {
				continue;
			}
			
			foreach ( $caps as $cap => $grant ) {
				if ( $grant ) {
					$role->add_cap( $cap );
				}
			}
		}
		
		// Store capability version
		update_option( 'as_php_checkup_caps_version', '1.2.1' );
	}

	/**
	 * Remove capabilities from roles
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function remove_capabilities() {
		foreach ( $this->role_caps as $role_name => $caps ) {
			$role = get_role( $role_name );
			
			if ( null === $role ) {
				continue;
			}
			
			foreach ( array_keys( $caps ) as $cap ) {
				$role->remove_cap( $cap );
			}
		}
		
		delete_option( 'as_php_checkup_caps_version' );
	}

	/**
	 * Filter user capabilities
	 *
	 * @since 1.2.1
	 * @param array   $allcaps All user capabilities.
	 * @param array   $caps Required capabilities.
	 * @param array   $args Arguments.
	 * @param WP_User $user User object.
	 * @return array
	 */
	public function filter_user_capabilities( $allcaps, $caps, $args, $user ) {
		// Map legacy 'manage_options' to our custom caps for backward compatibility
		if ( isset( $allcaps['manage_options'] ) && $allcaps['manage_options'] ) {
			foreach ( array_keys( $this->capabilities ) as $cap ) {
				$allcaps[ $cap ] = true;
			}
		}
		
		// Apply capability filters
		foreach ( $this->capabilities as $cap => $description ) {
			if ( isset( $allcaps[ $cap ] ) ) {
				$allcaps[ $cap ] = apply_filters( 
					'as_php_checkup_user_can_' . str_replace( 'as_php_checkup_', '', $cap ),
					$allcaps[ $cap ],
					$user
				);
			}
		}
		
		return $allcaps;
	}

	/**
	 * Check if user has specific capability
	 *
	 * @since 1.2.1
	 * @param string $capability Capability to check.
	 * @param int    $user_id Optional user ID.
	 * @return bool
	 */
	public function user_can( $capability, $user_id = null ) {
		if ( null === $user_id ) {
			return current_user_can( $capability );
		}
		
		return user_can( $user_id, $capability );
	}

	/**
	 * Check multiple capabilities
	 *
	 * @since 1.2.1
	 * @param array $capabilities Capabilities to check.
	 * @param bool  $require_all Whether all capabilities are required.
	 * @param int   $user_id Optional user ID.
	 * @return bool
	 */
	public function user_can_any( $capabilities, $require_all = false, $user_id = null ) {
		foreach ( $capabilities as $capability ) {
			$can = $this->user_can( $capability, $user_id );
			
			if ( $require_all && ! $can ) {
				return false;
			} elseif ( ! $require_all && $can ) {
				return true;
			}
		}
		
		return $require_all;
	}

	/**
	 * Add capabilities management menu
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function add_capabilities_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		add_submenu_page(
			'tools.php',
			__( 'PHP Checkup Permissions', 'as-php-checkup' ),
			__( 'PHP Checkup Permissions', 'as-php-checkup' ),
			'manage_options',
			'as-php-checkup-capabilities',
			array( $this, 'render_capabilities_page' )
		);
	}

	/**
	 * Render capabilities management page
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function render_capabilities_page() {
		// Handle form submission
		if ( isset( $_POST['save_capabilities'] ) ) {
			check_admin_referer( 'as_php_checkup_save_capabilities' );
			$this->save_capabilities();
		}
		
		$roles = wp_roles()->roles;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PHP Checkup Permissions', 'as-php-checkup' ); ?></h1>
			
			<form method="post">
				<?php wp_nonce_field( 'as_php_checkup_save_capabilities' ); ?>
				
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Role', 'as-php-checkup' ); ?></th>
							<?php foreach ( $this->capabilities as $cap => $description ) : ?>
								<th title="<?php echo esc_attr( $description ); ?>">
									<?php echo esc_html( str_replace( 'as_php_checkup_', '', $cap ) ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $roles as $role_name => $role_info ) : 
							$role = get_role( $role_name );
							?>
							<tr>
								<td><strong><?php echo esc_html( $role_info['name'] ); ?></strong></td>
								<?php foreach ( $this->capabilities as $cap => $description ) : ?>
									<td>
										<input type="checkbox" 
										       name="capabilities[<?php echo esc_attr( $role_name ); ?>][<?php echo esc_attr( $cap ); ?>]"
										       value="1"
										       <?php checked( isset( $role->capabilities[ $cap ] ) && $role->capabilities[ $cap ] ); ?>
										       <?php disabled( 'administrator' === $role_name ); ?> />
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<p class="submit">
					<button type="submit" name="save_capabilities" class="button button-primary">
						<?php esc_html_e( 'Save Permissions', 'as-php-checkup' ); ?>
					</button>
				</p>
			</form>
			
			<div class="capability-descriptions">
				<h3><?php esc_html_e( 'Capability Descriptions', 'as-php-checkup' ); ?></h3>
				<ul>
					<?php foreach ( $this->capabilities as $cap => $description ) : ?>
						<li>
							<strong><?php echo esc_html( str_replace( 'as_php_checkup_', '', $cap ) ); ?>:</strong>
							<?php echo esc_html( $description ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Save capabilities from form
	 *
	 * @since 1.2.1
	 * @return void
	 */
	private function save_capabilities() {
		if ( ! isset( $_POST['capabilities'] ) ) {
			return;
		}
		
		$submitted_caps = wp_unslash( $_POST['capabilities'] );
		
		foreach ( $submitted_caps as $role_name => $caps ) {
			// Skip administrator role
			if ( 'administrator' === $role_name ) {
				continue;
			}
			
			$role = get_role( $role_name );
			if ( null === $role ) {
				continue;
			}
			
			// Remove all plugin capabilities first
			foreach ( $this->capabilities as $cap => $description ) {
				$role->remove_cap( $cap );
			}
			
			// Add selected capabilities
			foreach ( $caps as $cap => $grant ) {
				if ( $grant && isset( $this->capabilities[ $cap ] ) ) {
					$role->add_cap( $cap );
				}
			}
		}
		
		// Show admin notice
		add_settings_error(
			'as_php_checkup_capabilities',
			'capabilities_updated',
			__( 'Permissions updated successfully.', 'as-php-checkup' ),
			'success'
		);
	}

	/**
	 * Get all plugin capabilities
	 *
	 * @since 1.2.1
	 * @return array
	 */
	public function get_capabilities() {
		return $this->capabilities;
	}

	/**
	 * Check if capabilities need upgrade
	 *
	 * @since 1.2.1
	 * @return bool
	 */
	public function needs_capability_upgrade() {
		$current_version = get_option( 'as_php_checkup_caps_version', '0' );
		return version_compare( $current_version, '1.2.1', '<' );
	}

	/**
	 * Upgrade capabilities
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function upgrade_capabilities() {
		if ( $this->needs_capability_upgrade() ) {
			$this->add_capabilities();
		}
	}
}