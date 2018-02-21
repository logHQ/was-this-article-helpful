<?php
/*
Plugin Name: Was this article helpful
Plugin URI: https://login.plus
Description: Add "Was this article helpful?" at the end or start or both of article with thumbs up and thumbs down . Thumbs up would make to share and thumbs down would make to provide feedback to author via email
Version: 1.0
Author: LogHQ
Author URI: https://login.plus/
Author Email: support@login.plus
Text Domain: was-this-article-helpful
Domain Path: /languages
Credit: https://wordpress.org/plugins/wp-article-feedback/
License: GNU General Public License see <http://www.gnu.org/licenses/>.

*/

class Articlefeedback {
	private static $instance;
    
    const VERSION = 1.0;

	private static function has_instance() {
		return isset( self::$instance ) && null != self::$instance;
	}

	public static function get_instance() {
		if ( ! self::has_instance() ) {
			self::$instance = new Articlefeedback;
		}
		return self::$instance;
	}

	public static function setup() {
		self::get_instance();
	}

	protected function __construct() {
		if ( ! self::has_instance() ) {
			$this->init();
		}
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_plugin_admin_styles' ) );
		add_shortcode( 'feedback_prompt', array( $this, 'feedback_content' ) );
		// register our settings page
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		// register setting
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// content Filter wp the_content
		add_filter( 'the_content', array( $this, 'append_feedack_html' ) );
		register_activation_hook( __FILE__, array( $this, 'load_defaults' ) );
		//Ajax to send feedback by mail	
		add_action('wp_ajax_join_mailinglist', array( $this,'feedback_sendmail'));
		add_action('wp_ajax_nopriv_join_mailinglist', array( $this,'feedback_sendmail'));
		//Language Support For Was this article helpful?
		add_action( 'plugins_loaded', array($this,'feedback_load_textdomain') );

	}

	/**
	* Feedback Plugin styles.
	*
	* @since 1.0
	*/
	public function register_plugin_styles() {
		global $wp_styles;
		$feedback_options = $this->get_feedback_options('feedback_options');
		$fontsize=$feedback_options['ss-font-size'];
		$Upcolor=($feedback_options['ss-thumbs-up']!="")?$feedback_options['ss-thumbs-up']:'#FF3234';
		$Downcolor=($feedback_options['ss-thumbs-down']!="")?$feedback_options['ss-thumbs-down']:'#5C7ED7';

		wp_enqueue_style( 'font-awesome-styles', plugins_url( 'assets/css/font-awesome.min.css', __FILE__ ), array(), self::VERSION, 'all' );
		wp_enqueue_style( 'feedback-front-styles', plugins_url( 'assets/css/front-feedback-styles.css', __FILE__ ), array(), self::VERSION, 'all' );
		wp_enqueue_script( 'feedback-front-script', plugins_url( 'assets/js/article-feedback.js', __FILE__ ), array('jquery'), self::VERSION, 'all' );
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		wp_localize_script( 'feedback-front-script', 'FeedbackAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_add_inline_style( 'feedback-front-styles', 'a.m-feedback-prompt__button.m-feedback-prompt__social.yes, a.m-feedback-prompt__button.m-feedback-prompt_form.no {
											color: '.$Upcolor.';
											font-size:'.$fontsize.'em;
											}
											a.m-feedback-prompt__button.m-feedback-prompt_form.no {
    										color: '.$Downcolor.';
    										font-size:'.$fontsize.'em;
											}' );
	}

	/**
    * Add custom css for admin section
    */
    function register_plugin_admin_styles(){
       	wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script('feedback-admin-custom-script', plugins_url( 'assets/js/article-feedback-admin.js', __FILE__ ), array('jquery','wp-color-picker'), self::VERSION, 'all' );
        wp_register_style( 'feedback-admin_css', plugin_dir_url(__FILE__) . '/assets/css/admin-feedback.css', false );
        wp_enqueue_style( 'feedback-admin_css' );
                
    }

	/**
	* Load plugin textdomain.
	*/
	function feedback_load_textdomain() {
		load_plugin_textdomain( 'wp-article-feedback', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' ); 
	}
	
	/**
	* Feedback Content.
	*/
	public function feedback_content() {
		global $post;
		$feedback_options = $this->get_feedback_options('feedback_options');
		$title_phrase=$feedback_options['ss-title-phrase'];
		if($title_phrase!=""):
			$title_phrase=$title_phrase;	
		else:
			$title_phrase=__('Was this article helpful?','wp-article-feedback');
		endif;	

		$onclick="javascript:window.open(this.href,
  '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');return false;";
		return '<div class="m-entry__feedback"><div class="m-feedback-prompt">
		  <h4 class="m-feedback-prompt__header">'.$title_phrase.'</h4>
		  <a href="#" class="m-feedback-prompt__button m-feedback-prompt__social m-feedback-prompt__social_thumbsup yes" data-analytics-link="feedback-prompt:yes">
		    <i class="fa fa-thumbs-up">&nbsp;</i>
		  </a>
		  <a href="#" class="m-feedback-prompt__button m-feedback-prompt_form no" data-analytics-link="feedback-prompt:no">
		    <i class="fa fa-thumbs-down">&nbsp;</i>
		  </a><br>
		  <div class="m-feedback-prompt__display m-feedback-prompt__social yes">
		    <p class="m-feedback-prompt__text">'.__('Awesome, share it:','wp-article-feedback').'</p>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon facebook fa fa-facebook" href="https://www.facebook.com/sharer/sharer.php?u='.urldecode(get_permalink($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">'.__('Share','wp-article-feedback').'</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon twitter fa fa-twitter" href="https://twitter.com/intent/tweet?url='.urldecode(get_permalink($post->ID)).'&text='.urldecode(get_the_title($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">'.__('Tweet','wp-article-feedback').'</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon googleplus fa fa-google-plus" href="https://plus.google.com/share?url='.urldecode(get_permalink($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">'.__('Google Plus','wp-article-feedback').'</span>
		    </a>
		    <a class="m-feedback-prompt__social--button p-button-social has-icon linkedin fa fa-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url='.urldecode(get_permalink($post->ID)).'&title='.urldecode(get_the_title($post->ID)).'" onclick="'.$onclick.'">
		      <span class="p-button-social__social-text">'.__('LinkedIn','wp-article-feedback').'</span>
		    </a>
		  </div>
		  	<div class="m-feedback-prompt__display m-feedback-prompt__form no">
		  	<div class="thanks feedback-nodisplayall"><h2>'.__('Thanks!','wp-article-feedback').'<h2><div class="m-contact"><p>'.__('Thanks for getting in touch with us.','wp-article-feedback').'</p></div></div>
		    <form id="contact-form" class="new_support_request" action="" accept-charset="UTF-8" method="post">
		    '.wp_nonce_field(-1,'authenticity_token',true, false).'
		      <input value="'.urldecode(get_permalink($post->ID)).'" type="hidden" name="currenturl" id="currenturl">
		      <input value="'.urldecode(get_the_title($post->ID)).'" type="hidden" name="currenttitle" id="currenttitle">
		      <label class="is-required">'.__('Help us improve. Give us your feedback:','wp-article-feedback').'</label>
		      <textarea class="p-input__textarea" name="feedbackmessage" id="feedbackmessage"></textarea>
		      <label class="is-required">'.__('Your Full Name:','wp-article-feedback').'</label>
		      <input class="p-input__text" type="text" name="feedbackfullname" id="feedbackfullname">
		      <label class="is-required">'.__('Your email address:','wp-article-feedback').'</label>
		      <input class="p-input__text" type="text" name="mailinglistemail" id="mailinglistemail">
		      <div class="feedback-message" id="feedback-message"></div>
		      <div class="__submit">
		        <input type="submit" name="commit" value="'.__('Submit','wp-article-feedback').'" class="p-button" id="submit-contact-form" data-analytics-link="feedback-prompt:submit">
		      </div>
			</form>
			</div>
			</div>
			</div>';
	}

		/**
		* Feedback Append HTML with Content with Thumbs Up and Down.
		*
		*/
		public function append_feedack_html( $content ) {

		$feedack_options = $this->get_feedback_options('feedback_options');
		
		// get current post's id
		global $post;
		$post_id = $post->ID;
		
		if( in_array($post_id,explode(',',$feedack_options['ss-exclude-on'])) )
			return $content;
		if( is_home() && !in_array( 'home', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_single() && !in_array( 'posts', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_page() && !in_array( 'pages', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		if( is_archive() && !in_array( 'archive', (array)$feedack_options['ss-show-on'] ) )
			return $content;
		
		$feedback_html_markup = $this->feedback_content();
		
		if( is_array($feedack_options['ss-select-position']) && in_array('before-content', $feedack_options['ss-select-position']) )
			$content = $feedback_html_markup.$content;
		if( is_array($feedack_options['ss-select-position']) && in_array('after-content', (array)$feedack_options['ss-select-position']) )
			$content .= $feedback_html_markup;
		return $content;

	}
	public function load_defaults(){

		update_option( 'feedback_options', $this->get_defaults() );

	}
	public function get_defaults($preset=true) {
		return array(
				'ss-select-position' => $preset ? array('before-content') : array(),
				'ss-show-on' => $preset ? array('pages', 'posts') : array(),
				'ss-title-phrase'=>'',
				'ss-exclude-on' => '',
				'ss-feedback-email'=>'',
				'ss-font-size'=>'2.4',
				'ss-thumbs-up'=>'#5C7ED7',
				'ss-thumbs-down'=>'#FF3234'
				);
		
	}

	public function register_settings(){

		register_setting( 'feedback_options', 'feedback_options' );

	}

	/**
	 * Add sub menu page in Settings for configuring plugin
	 *
	 */
	public function register_submenu(){

		add_submenu_page( 'options-general.php', 'Was this article helpful? settings', 'Was this article helpful?', 'activate_plugins', 'article-feeback-settings', array( $this, 'submenu_page' ) );

	}

	public function get_feedback_options() {
		return array_merge( $this->get_defaults(false), get_option('feedback_options') );
	}

	/*
	 * Callback for add_submenu_page for generating markup of page
	 */
	public function submenu_page() {
		?>
		<div class="wrap">
			<h2 class="boxed-header"><?php  _e('Was this article helpful? Settings','wp-article-feedback');?></h2>
			<div class="activate-boxed-highlight activate-boxed-option">
				<form method="POST" action="options.php">
				<?php settings_fields('feedback_options'); ?>
				<?php
				$feedback_options = get_option('feedback_options');
				?>
				<?php echo $this->admin_form($feedback_options); ?>
			</div>
			<div class="activate-use-option sidebox first-sidebox">
	          	<h3><?php  _e('Instruction to use Plugin','wp-article-feedback');?></h3>
	        	<hr />
	        	<h3><?php _e('Using Shortcode','wp-article-feedback');?></h3>
	        	<p><?php _e('You can place the shortcode','wp-article-feedback')?></p><p><code>[feedback_prompt]</code></p><p><?php _e('wherever you want to display the Was this article helpful?.','wp-article-feedback');?></p>
	        	<hr />
	        </div>
		</div>
		<?php
	}

	/**
	 * Admin form for Feedabck Settings
	 *
	 */
	public function admin_form( $feedback_options ){
	
		return '<table class="form-table settings-table">
			<tr>
				<th><label for="ss-select-postion">'.__('Select Position','wp-article-feedback').'</label></th>
				<td>
					<input type="checkbox" name="feedback_options[ss-select-position][]" id="before-content" class="css-checkbox" value="before-content" '.__checked_selected_helper( in_array( 'before-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
					<label for="before-content" class="css-label cb0">'.__('Before Content','wp-article-feedback').'</label>					
					<input type="checkbox" name="feedback_options[ss-select-position][]" id="after-content" class="css-checkbox" value="after-content" '.__checked_selected_helper( in_array( 'after-content', (array)$feedback_options['ss-select-position'] ),true, false,'checked' ).'>
					<label for="after-content" class="css-label cb0">'.__('After Content','wp-article-feedback').'</label>					
					
				</td>
			</tr>
			<tr>
				<th><label for="ss-select-postion">'.__('Show on','wp-article-feedback').'</label></th>
				<td>
					<input type="checkbox" name="feedback_options[ss-show-on][]" id="home-pages" class="css-checkbox" value="home" '.__checked_selected_helper( in_array( 'home', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					<label for="home-pages" class="css-label cb0">'.__('Home Page','wp-article-feedback').'</label>					
					<input type="checkbox" name="feedback_options[ss-show-on][]" id="pages" class="css-checkbox" value="pages" '.__checked_selected_helper( in_array( 'pages', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					<label for="pages" class="css-label cb0">'.__('Pages','wp-article-feedback').'</label>					
					<input type="checkbox" name="feedback_options[ss-show-on][]" id="posts" class="css-checkbox" value="posts" '.__checked_selected_helper( in_array( 'posts', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					<label for="posts" class="css-label cb0">'.__('Posts','wp-article-feedback').'</label>					
					<input type="checkbox" name="feedback_options[ss-show-on][]" id="archives" class="css-checkbox" value="archive" '.__checked_selected_helper( in_array( 'archive', (array)$feedback_options['ss-show-on'] ),true, false,'checked' ).'>
					<label for="archives" class="css-label cb0">'.__('Archives','wp-article-feedback').'</label>					
				</td>
			</tr>
			<tr>
				<th><label for="ss-title-phrase">'.__('Title Phrase (Was this article helpful?)','wp-article-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[ss-title-phrase]" value="'.$feedback_options['ss-title-phrase'].'">
					<small><em>'.__('Keep this section blank, if you want default phrase with its respective language or enter phrase of your choice, like Did this page help you? or Was this review useful?','wp-article-feedback').' </em></small>
				</td>
			</tr>

			<tr>
				<th><label for="ss-exclude-on">'.__('Exclude on','wp-article-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[ss-exclude-on]" value="'.$feedback_options['ss-exclude-on'].'">
					<small><em>'.__('Comma seperated post id\'s Eg:','wp-article-feedback').' </em><code>1207,1222</code></small>
				</td>
			</tr>
			<tr>
				<th><label for="ss-font-size">'.__('Font Size','wp-article-feedback').'</label></th>
				<td>
					<input type="range" min="0.1" max="10"  id="fader" step="0.1" name="feedback_options[ss-font-size]" value="'.$feedback_options['ss-font-size'].'"><output for="fader" id="fontsize">'.$feedback_options['ss-font-size'].'em</output>
					<small><em>'.__('Thumbs Up and Down Font Size','wp-article-feedback').' </em></small>
				</td>
			</tr>
			<tr>
				<th><label for="ss-thumbs-up">'.__('Thumbs Up color','wp-article-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[ss-thumbs-up]" id="ssthumbsup" data-default-color="#5C7ED7" value="'.$feedback_options['ss-thumbs-up'].'">
				</td>
			</tr>
			<tr>
				<th><label for="ss-thumbs-down">'.__('Thumbs Down Color','wp-article-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[ss-thumbs-down]" id="ssthumbsdown" data-default-color="#FF3234" value="'.$feedback_options['ss-thumbs-down'].'">
				</td>
			</tr>
			<tr>
				<th><label for="ss-select-emailsetting">'.__('Thumbs Down Email To','wp-article-feedback').'</label></th>
				<td>
					<input type="text" name="feedback_options[ss-feedback-email]" value="'.$feedback_options['ss-feedback-email'].'">
					<small><em>'.__('If Empty Then Feedback Mail would Directly Go To Post/Page Author\'s Email','wp-article-feedback').'</em></small>
				</td>

			</tr>
		</table>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="'.__('Save Changes','wp-article-feedback').'">
		</p>
	</form>';

	
	}
	/**
	* Feedback send article Feedback by mail to article author or custom provided mail id
	*
	*/

	function feedback_sendmail()
	{
		$feedback_options = get_option('feedback_options');
		$to=sanitize_email($feedback_options['ss-feedback-email']);
		$to=($to=="")?get_the_author_meta( 'user_email' ):$to;
		$email = sanitize_email($_POST['email']);
		$name=sanitize_text_field($_POST['name']);
		$message=sanitize_text_field($_POST['message']);
		$url=esc_url($_POST['url']);
		$title=sanitize_text_field($_POST['title']);
		$emailError="";
		$emailError="";
		
		
		if(sanitize_email($_POST['email']) === '')  {
			$emailError = __('Please enter your email address.','wp-article-feedback');
			$hasError = true;
		} else if (!preg_match("/^[[:alnum:]][a-z0-9_.-]*@[a-z0-9.-]+\.[a-z]{2,4}$/i", trim($_POST['email']))) {
			$emailError = __('You entered an invalid email address.','wp-article-feedback');
			$hasError = true;
		} else {
			$email = sanitize_email($_POST['email']);
		}

		$allMesage=__('Feedback For:','wp-article-feedback').esc_html($title)."<br/>".__('Feedback URL:','wp-article-feedback').esc_url($url)."<br/>".__('Feedack Message:','wp-article-feedback')."<br/>".esc_html($message)."<br/>".__('Feedback From: ','wp-article-feedback').$email."<br/>".__('Full Name: ','wp-article-feedback').esc_html($name) ;

		if(!empty($email) && !isset($hasError)) {
	   
	    $headers = 'From: '.get_bloginfo( 'admin_email' ) ."\r\n".'Reply-To: '.$email;
	 	add_filter( 'wp_mail_content_type', function( $content_type ) {
		return 'text/html';
		});
	         if($emailError=="") {
	        	wp_mail( $to, __('Feedback For: ','wp-article-feedback').$title, $allMesage, $headers);
				echo 'success';

			}else {
				echo __('There was a problem. Please try again.','wp-article-feedback').$emailError;
			}
		}
		die();
	}


}

Articlefeedback::setup();
