<?php
/**
 * Plugin Name: Local Developer
 * Description: All functions for working on a local ENV
 * Version: 1.0.0
 */

/**
 * Global Definitions
 * @since  1.0.0 
 */
error_reporting(E_ALL & ~E_NOTICE);

define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', true );
define( 'FS_METHOD', 'direct' );
define( 'WP_CACHE', false );


/**
 * Easily dump variables
 * @since  1.0.0 
 */
function dump($data) {
	echo '<div style="margin: 10px; padding: 10px; border: 1px solid #ddd;">';
		highlight_string( var_export($data, true) );
	echo '</div>';
}

/**
 * Auto Update
 * @since  1.0.0 
 */
add_filter( 'auto_update_plugin', '__return_true' );
add_filter( 'auto_update_theme', '__return_true' );

/**
 * Local indicator at top of every page
 * @since  1.0.0 
 */
class Local_Indicator {

	/**
	 * Initialize
	 *
	 * @since  1.1.0
	 */
	public function __construct() {

		// Front-end
		add_action( 'wp_enqueue_scripts', 		array( $this, 'jquery' ) );
		add_action( 'wp_footer', 				array( $this, 'indicator' ) );

		// Admin
		add_action( 'admin_enqueue_scripts', 	array( $this, 'jquery' ) );
		add_action( 'admin_footer', 			array( $this, 'indicator' ) );
		
		// Login Form
		add_action( 'login_enqueue_scripts', 	array( $this, 'jquery' ) );
		add_action( 'login_footer', 			array( $this, 'indicator' ) );

	}

	/**
	 * Enqueue jQuery
	 *
	 * @since  1.1.0
	 */
	public function jquery() {
		wp_enqueue_script('jquery');
	}

	/**
	 * Enqueue indiciator
	 *
	 * @since  1.1.0
	 */
	public function indicator() {		
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('body').append( '<div style="background: red; height: 3px; position: fixed; width: 100%; top: 0; left: 0; z-index:2147483647;"></div>' );
			});
		</script>
		<?php
	}

}

/**
 * Allows developer bypass of login credentials at /wp-admin
 * @author Stephen Carnam
 * @link   http://steveorevo.com
 * @since  1.0.0 
 */
class Bypass_Login {

	/**
	 * The ID of this class.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $plugin_name 
	 */
	private $plugin_name = 'bypass-login';

	/**
	 * The version of this class.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $version 
	 */
	private $version = '1.2.2';

	/**
	 * Initialize
	 *
	 * @since  1.1.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_nopriv_bypass_login', array( $this, 'wp_ajax_nopriv_bypass_login' ) );
		add_action( 'wp_ajax_bypass_login', array( $this, 'wp_ajax_nopriv_bypass_login' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_jquery' ) );
		add_action( 'login_form', array( $this, 'enqueue_javascript' ) );
		add_action( 'login_form', array( $this, 'login_form' ) );

	}

	/**
	 * Enqueue jQuery
	 *
	 * @since  1.1.0
	 */
	public function enqueue_jquery() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Enqueue Javascript
	 *
	 * @since  1.1.0
	 */
	public function enqueue_javascript() {
		
		?>

		<script type="text/javascript">

			(function($){

				$(function(){

					$("#bypass_login").change(function(){

						var user_id = $(this).val();

						if (user_id !== '-1' ) {

							var login = {
								action: 'bypass_login',
								user_id: user_id
							};

							$.post('<?php echo $this->get_admin_url(); ?>', login, function(r) {
								
								if (r < 1) {
									alert('Login error: ' + r);
								}else{
									window.location.href = '<?php echo $this->redirect_to_admin(); ?>',
									$('#wp-submit').attr('disabled', 'disabled').val('Logging in...');
								}

							});

						}

					});

				});

			})(jQuery);

		</script>

		<?php
	}

	/**
	 * Get all users
	 *
	 * @since  1.1.0
	 */
	public function get_users() {

		$args = array(
			'number' 	=> '100',
			'orderby' 	=> 'id'
		);

		$users = get_users( $args );

		return $users;

	}

	/**
	 * Redirect URL or default to admin 
	 *
	 * @since  1.1.0
	 */
	public function redirect_to_admin() { 
		
		$url = get_admin_url();

		if ( isset( $_REQUEST['redirect_to'] ) ) {

			$url = $_REQUEST['redirect_to'];

		}

		return $url;

	}

	/**
	 * AJAX admin URL 
	 *
	 * @since  1.2.0
	 */
	public function get_admin_url() { 	

		return admin_url( 'admin-ajax.php' );

	}

	/**
	 * Get the label for the select form
	 *
	 * @since  1.1.0
	 */
	public function login_form_label() {

		return sprintf('<label for="bypass_login">%s</label>',
			esc_html__('Bypass Login', $this->plugin_name)
		);

	}

	/**
	 * Get options values
	 *
	 * @since 1.1.0
	 */
	public function get_options_values() {

		$options = '';

		foreach ( $this->get_users() as $user ) {

			$wp_roles = new WP_Roles();
			
			$cap = $user->{$user->cap_key};

			$roles = '';

			$sep = '';

			foreach ( $wp_roles->role_names as $role => $name ) {

				if ( array_key_exists( $role, $cap ) ) {

					$roles .= $sep . $role;
					$sep = ', ';

				}

			}

			$options .= sprintf('<option value="%s">%s (%s)</option>',
				$user->ID,
				$user->user_login,
				$roles
			);

		}

		return $options;

	}

	/**
	 * Login form select field
	 *
	 * @since  1.1.0
	 */
	public function login_form_select() {
		
		return sprintf('<select id="bypass_login" style="display:block;margin:5px 0 20px; width:%s"><option value="-1" selected="selected">%s</option>%s%s</select>',
			esc_attr('100%'),
			esc_html__('Choose username...', $this->plugin_name),
			$this->get_options_values(),
			$this->redirect_to_admin()
		);

	}

	/**
	 * Print login form
	 *
	 * @since  1.1.0
	 */
	public function login_form() {
	
		printf('<p>%s%s</p>',
			$this->login_form_label(),
			$this->login_form_select()
		);			

	}

	/**
	 * Login as the user and return success
	 *
	 * @since  1.1.0
	 */
	public function wp_ajax_nopriv_bypass_login() {

		$user_id = intval( $_POST['user_id'] );

		wp_set_auth_cookie( $user_id, true );

		echo 1;

		die();

	}
}



add_action( 'muplugins_loaded', 'local_developer_init' );
/**
 * Kick to off
 * @since  1.0.0 
 * @return void
 */
function local_developer_init() {

	$local_indicator = new Local_Indicator();
	$bypass_login = new Bypass_Login();

}