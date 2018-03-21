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


/**
 * Plugin URI:  http://www.github.com/billerickson/be-media-from-production
 * Description: Uses local media when it's available, and uses the production server for rest.
 * Author:      Bill Erickson
 * Author URI:  http://www.billerickson.net
 * Version:     1.4.0
 * Text Domain: be-media-from-production
 * Domain Path: languages
 *
 * BE Media from Production is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * BE Media from Production is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BE Media from Production. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Media_From_Production
 * @author     Bill Erickson
 * @since      1.0.0
 * @license    GPL-2.0+
 */

/**
 * Main Class
 * @since 1.0.0
 * @package Media_From_Production
 */
class Media_From_Production {

	/**
	 * Production URL
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $production_url = '';

	/**
	 * Holds list of upload directories
	 * Can set manually here, or allow function below to automatically create it
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $directories = array();
	
	/**
	 * Start Month
	 * 
	 * @since 1.0.0
	 * @var int
	 */
	public $start_month = false;
	
	/**
	 * Start Year 
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $start_year = false;
	
	/**
	 * Primary constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Set upload directories
		add_action( 'init',                               array( $this, 'set_upload_directories' )     );
		
		// Update Image URLs
		add_filter( 'wp_get_attachment_image_src',        array( $this, 'image_src'              )     );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'image_attr'             ), 99 );
		add_filter( 'wp_prepare_attachment_for_js',       array( $this, 'image_js'               ), 10, 3 );
		add_filter( 'the_content',                        array( $this, 'image_content'          )     );
		
	}
	
	/**
	 * Set upload directories
	 *
	 * @since 1.0.0
	 */
	function set_upload_directories() {
		
		if( empty( $this->directories ) )
			$this->directories = $this->get_upload_directories();
		
	}

	/**
	 * Determine Upload Directories
	 *
	 * @since 1.0.0
	 */
	function get_upload_directories() {
	
		// Include all upload directories starting from a specific month and year
		$month = str_pad( apply_filters( 'Media_From_Production_start_month', $this->start_month ), 2, 0, STR_PAD_LEFT );
		$year = apply_filters( 'Media_From_Production_start_year', $this->start_year );
	
		$upload_dirs = array();

		if( $month && $year ) {
			for( $i = 0; $year . $month <= date( 'Ym' ); $i++ ) {
				$upload_dirs[] = $year . '/' . $month;
				$month++;
				if( 13 == $month ) {
					$month = 1;
					$year++;
				}
				$month = str_pad( $month, 2, 0, STR_PAD_LEFT );
			}
		}
		
		return apply_filters( 'Media_From_Production_directories', $upload_dirs );
			
	}

	/**
	 * Modify Main Image URL
	 *
	 * @since 1.0.0
	 * @param array $image
	 * @return array $image
	 */
	function image_src( $image ) {
	
		if( isset( $image[0] ) )
			$image[0] = $this->update_image_url( $image[0] );
		return $image;
				
	}
	
	/**
	 * Modify Image Attributes
	 *
	 * @since 1.0.0
	 * @param array $attr
	 * @return array $attr
	 */
	function image_attr( $attr ) {
		
		if( isset( $attr['srcset'] ) )
			$attr['srcset'] = $this->update_image_url( $attr['srcset'] );
		return $attr;

	}
	
	/**
	 * Modify Image for Javascript
	 * Primarily used for media library
	 *
	 * @since 1.3.0
	 * @param array      $response   Array of prepared attachment data
	 * @param int|object $attachment Attachment ID or object
	 * @param array      $meta       Array of attachment metadata
	 * @return array     $response   Modified attachment data
	 */
	function image_js( $response, $attachment, $meta ) {
	
		if( isset( $response['url'] ) )
			$response['url'] = $this->update_image_url( $response['url'] );
		
		foreach( $response['sizes'] as &$size ) {
			$size['url'] = $this->update_image_url( $size['url'] );
		}	
			
		return $response;
	}

	/**
	 * Modify Images in Content
	 *
	 * @since 1.2.0
	 * @param string $content
	 * @return string $content
	 */
	function image_content( $content ) {
		$upload_locations = wp_upload_dir();

		$regex = '/https?\:\/\/[^\" ]+/i';
		preg_match_all($regex, $content, $matches);

		foreach( $matches[0] as $url ) {
			if( false !== strpos( $url, $upload_locations[ 'baseurl' ] ) ) {
				$new_url = $this->update_image_url( $url );
				$content = str_replace( $url, $new_url, $content );
			}
		}
		return $content;
	}

	/**
	 * Convert a URL to a local filename
	 *
	 * @since 1.4.0
	 * @param string $url
	 * @return string $local_filename
	 */
	function local_filename( $url ) {
		$upload_locations = wp_upload_dir();
		$local_filename = str_replace( $upload_locations[ 'baseurl' ], $upload_locations[ 'basedir' ], $url );
		return $local_filename;
	}

	/**
	 * Determine if local image exists
	 *
	 * @since 1.4.0
	 * @param string $url
	 * @return boolean
	 */
	function local_image_exists( $url ) {
		return file_exists( $this->local_filename( $url ) );
	}

	/**
	 * Update Image URL
	 *
	 * @since 1.0.0
	 * @param string $image_url
	 * @return string $image_url
	 */
	function update_image_url( $image_url ) {

		if( ! $image_url )
			return $image_url;

		if ( $this->local_image_exists( $image_url ) ) {
			return $image_url;
		}
		
		$production_url = esc_url( apply_filters( 'Media_From_Production_url', $this->production_url ) );
		if( empty( $production_url ) )
			return $image_url;
	
		$exists = false;
		$upload_dirs = $this->directories;
		if( $upload_dirs ) {		
			foreach( $upload_dirs as $option ) {
				if( strpos( $image_url, $option ) ) {
					$exists = true;
				}
			}
		}
				
		if( ! $exists ) {
			$image_url = str_replace( home_url(), $production_url, $image_url );
		}
			
		return $image_url;
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
	$media_from_production = new Media_From_Production();

}