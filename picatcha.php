<?php

require_once('wp-plugin-picatcha.php');

if (!class_exists('Picatcha')) {
    class Picatcha extends WPPluginPicatcha {
        // member variables
        private $saved_error;
        
        // php 4 constructor
        function Picatcha($options_name) {
            $args = func_get_args();
            call_user_func_array(array(&$this, "__construct"), $args);
        }
        
        // php 5 constructor
        function __construct($options_name) {
            parent::__construct($options_name);
            
            $this->register_default_options();
            
            // require the picatcha library
            $this->require_library();
            
            // register the hooks
            $this->register_actions();
            $this->register_filters();
        }
        
        function register_actions() {
            // load the plugin's textdomain for localization
            add_action('init', array(&$this, 'load_textdomain'));

            // styling
            add_action('wp_head', array(&$this, 'register_stylesheets')); // make unnecessary: instead, inform of classes for styling
            add_action('admin_head', array(&$this, 'register_stylesheets')); // make unnecessary: shouldn't require styling in the options page
            
            if ($this->options['show_in_registration'])
                add_action('login_head', array(&$this, 'registration_style')); // make unnecessary: instead use jQuery and add to the footer?

            // options
            register_activation_hook(WPPluginPicatcha::path_to_plugin_directory() . '/wp-picatcha.php', array(&$this, 'register_default_options')); // this way it only happens once, when the plugin is activated
            add_action('admin_init', array(&$this, 'register_settings_group'));

            // only register the hooks if the user wants picatcha on the registration page
            if ($this->options['show_in_registration']) {
                // picatcha form display
                if ($this->is_multi_blog())
                    add_action('signup_extra_fields', array(&$this, 'show_picatcha_in_registration'));
                else
                    add_action('register_form', array(&$this, 'show_picatcha_in_registration'));
            }
            
            if ($this->options['show_in_lost_password']){
                add_action('lostpassword_form', array(&$this, 'show_picatcha_in_registration'));
            }
            
            if($this->options['show_in_login']){ //$this->options['show_in_login']
                add_action('login_form', array(&$this, 'show_picatcha_in_registration'));
            }

            // only register the hooks if the user wants picatcha on the comments page
            if ($this->options['show_in_comments']) {
                add_action('comment_form', array(&$this, 'show_picatcha_in_comments'));
                add_action('wp_footer', array(&$this, 'save_comment_script')); // preserve the comment that was entered

                // picatcha comment processing (look into doing all of this with AJAX, optionally)
                add_action('wp_head', array(&$this, 'saved_comment'), 0);
                add_action('preprocess_comment', array(&$this, 'check_comment'), 0);
                add_action('comment_post_redirect', array(&$this, 'relative_redirect'), 0, 2);
            }

            // administration (menus, pages, notifications, etc.)
            add_filter("plugin_action_links", array(&$this, 'show_settings_link'), 10, 2);

            add_action('admin_menu', array(&$this, 'add_settings_page'));
            
            // admin notices
            add_action('admin_notices', array(&$this, 'missing_keys_notice'));
        }
        
        function register_filters() {
            // only register the hooks if the user wants picatcha on the registration page
            if ($this->options['show_in_registration']) {
                // picatcha validation
                if ($this->is_multi_blog())
                    add_filter('wpmu_validate_user_signup', array(&$this, 'validate_picatcha_response_wpmu'));
                else
                    add_filter('registration_errors', array(&$this, 'validate_picatcha_response'));
                
                // register for buddy press
                add_action('bp_signup_validate', array(&$this,'picatcha_check_bp'));
                add_action('bp_before_registration_submit_buttons', array(&$this,'bph_picatcha'));
            }
            
            if($this->options['show_in_login']){
                if($this->is_multi_blog()){
                    add_filter('login_redirect', array(&$this, 'validate_picatcha_response_redirect'),10,3);
                    add_filter('login_errors', array(&$this, 'validate_picatcha_response_wpmu'));
                }
                else{
                    add_filter('login_redirect', array(&$this, 'validate_picatcha_response_redirect'),10,3);
                    add_filter('login_errors', array(&$this, 'validate_picatcha_response'));
                    
                }
                
            }
            
            if($this->options['show_in_lost_password']){
                // picatcha validation
                if ($this->is_multi_blog())
                    add_filter('lostpassword_post', array(&$this, 'validate_picatcha_response'));
                else
                    add_filter('lostpassword_post', array(&$this, 'check_picatcha_lost_password')); //validate_picatcha_response
                    
            }
        }
        
        function load_textdomain() {
            load_plugin_textdomain('picatcha', false, 'languages');
        }
        
        // set the default options
        function register_default_options() {
            if ($this->options)
               return;
           
            $option_defaults = array();
           
            $old_options = WPPluginPicatcha::retrieve_options("picatcha");
           
            if ($old_options) {
               $option_defaults['public_key'] = $old_options['pubkey']; // the public key for Picatcha
               $option_defaults['private_key'] = $old_options['privkey']; // the private key for Picatcha

               // placement
               $option_defaults['show_in_comments'] = $old_options['re_comments']; // whether or not to show Picatcha on the comment post
               $option_defaults['show_in_registration'] = $old_options['re_registration']; // whether or not to show Picatcha on the registration page
               $option_defaults['show_in_lost_password'] = $old_options['show_in_lost_password']; // whether or not to show when the user loses his/her password
               $option_defaults['show_in_login'] = $old_options['show_in_login']; // whether or not to require when the user is logging in
               $option_defaults['timedelta'] = $old_options['timedelta']; //The minimum amount of time a visitor must be on the page in order to post a legit comment
               $option_defaults['timedelta_activation'] = $old_options['timedelta_activation']; // Activation of timedelta
               $option_defaults['picatcha_timedelta_dropdown'] = $old_options['picatcha_timedelta_dropdown']; // Where comments go to when caught by TimeDelta
               $option_defaults['send_repudata'] = $old_option['send_repudata']; // Repudata on
               $option_defaults['reputation_user_levels'] = $old_option['reputation_user_levels']; // Repudata choice
               

               // bypass levels
               $option_defaults['bypass_for_registered_users'] = ($old_options['re_bypass'] == "on") ? 1 : 0; // whether to skip Picatcha for registered users
               $option_defaults['minimum_bypass_level'] = $old_options['re_bypasslevel']; // who doesn't have to do the Picatcha (should be a valid WordPress capability slug)

               if ($option_defaults['minimum_bypass_level'] == "level_10") {
                  $option_defaults['minimum_bypass_level'] = "activate_plugins";
               }

               // styling
               $option_defaults['comments_theme'] =              $old_options['re_theme']; // the default theme for Picatcha on the comment post
               $option_defaults['registration_theme'] =          $old_options['re_theme_reg']; // the default theme for Picatcha on the registration form
               $option_defaults['picatcha_language'] =           $old_options['re_lang']; // the default language for Picatcha
               $option_defaults['xhtml_compliance'] =            $old_options['re_xhtml']; // whether or not to be XHTML 1.0 Strict compliant
               $option_defaults['comments_tab_index'] =          $old_options['re_tabindex']; // the default tabindex for Picatcha
               $option_defaults['registration_tab_index'] = 30; // the default tabindex for Picatcha
               $option_defaults['display_powered_by'] =          $old_options['display_powered_by'];  //if it shows 'powered by picatcha'
               $option_defaults['picatcha_captcha_format'] =     $old_options['picatcha_captcha_format'];
               $option_defaults['picatcha_image_size'] =         $old_options['picatcha_image_size'];
               $option_defaults['picatcha_image_noise_level'] =  $old_options['picatcha_image_noise_level'];
               $option_defaults['picatcha_image_noise_type'] =   $old_options['picatcha_image_noise_type'];
               $option_defaults['picatcha_style_color'] =        $old_options['picatcha_style_color'];
               $option_defaults['picatcha_use_ssl'] =            $old_options['picatcha_use_ssl'];

               // error handling
               $option_defaults['no_response_error'] = $old_options['error_blank']; // message for no CAPTCHA response
               $option_defaults['incorrect_response_error'] = $old_options['error_incorrect']; // message for incorrect CAPTCHA response
            }
           
            else {
               // keys
               $option_defaults['public_key'] = ''; // the public key for Picatcha
               $option_defaults['private_key'] = ''; // the private key for Picatcha

               // placement
               $option_defaults['show_in_comments'] = 1; // whether or not to show Picatcha on the comment post
               $option_defaults['show_in_registration'] = 1; // whether or not to show Picatcha on the registration page
               $option_defaults['display_powered_by'] = 1; //whether or not to show "Powered by Picatcha"
               $option_defaults['show_in_lost_password'] = 0; //defaults to not show in password recovery page
               $option_defaults['show_in_login'] = 0; //defaults to not show when the user logs in
               $option_defaults['timedelta_activation'] = 0;
               $option_defaults['timedelta'] = 60; //Require a minimum of 60 seconds on the page in order to have a legit comment
               $option_defaults['picatcha_timedelta_dropdown'] = 'spam'; // By default, cause comments caught by TimeDelta to go to spam
               $option_defaults['send_repudata'] = 0;
               $option_defaults['reputation_user_levels'] = 0;

               // bypass levels
               $option_defaults['bypass_for_registered_users'] = 1; // whether to skip Picatcha for registered users
               $option_defaults['minimum_bypass_level'] = 'read'; // who doesn't have to do the Picatcha (should be a valid WordPress capability slug)

               // styling
               $option_defaults['comments_theme'] = 'red'; // the default theme for Picatcha on the comment post
               $option_defaults['registration_theme'] = 'red'; // the default theme for Picatcha on the registration form
               $option_defaults['picatcha_language'] = 'en'; // the default language for Picatcha
               $option_defaults['language_override'] = 0; //allow users to override the language on the Picatcha form
               $option_defaults['xhtml_compliance'] = 0; // whether or not to be XHTML 1.0 Strict compliant
               $option_defaults['comments_tab_index'] = 5; // the default tabindex for Picatcha
               $option_defaults['registration_tab_index'] = 30; // the default tabindex for Picatcha
               $option_defaults['picatcha_captcha_format'] = '2';
               $option_defaults['picatcha_image_size'] = 75;
               $option_defaults['picatcha_image_noise_level'] = 0;
               $option_defaults['picatcha_image_noise_type'] = 0;
               $option_defaults['picatcha_style_color'] = '2a1f19';
               $option_defaults['picatcha_use_ssl'] = 0;

               // error handling
               $option_defaults['no_response_error'] = '<strong>ERROR</strong>: Please select relevant images from the grid'; // message for no CAPTCHA response
               $option_defaults['incorrect_response_error'] = '<strong>ERROR</strong>: The images selected were incorrect. Please try again.'; // message for incorrect CAPTCHA response
            }
            
            // add the option based on what environment we're in
            WPPluginPicatcha::add_options($this->options_name, $option_defaults);
        }
        
        // require the Picatcha library
        function require_library() {
            require_once($this->path_to_plugin_directory() . '/picatcha/picatchalib.php');
        }
        
        // register the settings
        function register_settings_group() {
            register_setting("picatcha_options_group", 'picatcha_options', array(&$this, 'validate_options'));
        }
        
        // todo: make unnecessary
        function register_stylesheets() {
            $path = WPPluginPicatcha::url_to_plugin_directory() . '/picatchaWP.css';
                
            echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
        }
        
        // stylesheet information
        // todo: this 'hack' isn't nice, try to figure out a workaround
        function registration_style() {
            $path = WPPluginPicatcha::url_to_plugin_directory() . '/picatchaWP.css';
                
            echo '<link rel="stylesheet" type="text/css" href="' . $path . '" />';
            
            $width = 0; // the width of the picatcha form

            // every theme is 358 pixels wide except for the clean theme, so we have to programmatically handle that
            if ($this->options['registration_theme'] == 'clean')
                $width = 485;
            else
                $width = 360;

            echo <<<REGISTRATION
                <script type="text/javascript">
                window.onload = function() {
                    document.getElementById('login').style.width = '{$width}px';
                    if(document.getElementById('reg_passmail')){document.getElementById('reg_passmail').style.marginTop = '10px';}
                    if(document.getElementById('picatcha_widget_div')){document.getElementById('picatcha_widget_div').style.marginBottom = '10px';}
                };
                </script>
REGISTRATION;
        }
        
        function picatcha_enabled() {
            return ($this->options['show_in_comments'] || $this->options['show_in_registration']);
        }
        
        function keys_missing() {
            return (empty($this->options['public_key']) || empty($this->options['private_key']));
        }
        
        function create_error_notice($message, $anchor = '') {
            $options_url = admin_url('options-general.php?page=picatcha/picatcha.php') . $anchor;
            $error_message = sprintf(__($message . ' <a href="%s" title="WP-Picatcha Options">Fix this</a>', 'picatcha'), $options_url);
            
            echo '<div class="error"><p><strong>' . $error_message . '</strong></p></div>';
        }
        
        function missing_keys_notice() {
            if ($this->picatcha_enabled() && $this->keys_missing()) {
                $this->create_error_notice('You enabled Picatcha, but some of the Picatcha API Keys seem to be missing.');
            }
        }
        
        function validate_dropdown($array, $key, $value) {
            // make sure that the capability that was supplied is a valid capability from the drop-down list
            if (in_array($value, $array))
                return $value;
            else // if not, load the old value
                return $this->options[$key];
        }
        
        function validate_options($input) {
            // todo: make sure that 'incorrect_response_error' is not empty, prevent from being empty in the validation phase
            
            // trim the spaces out of the key, as they are usually present when copied and pasted
            // todo: keys seem to usually be 40 characters in length, verify and if confirmed, add to validation process
            $validated['public_key'] = trim($input['public_key']);
            $validated['private_key'] = trim($input['private_key']);
            
            $validated['show_in_comments'] = ($input['show_in_comments'] == 1 ? 1 : 0);
            $validated['bypass_for_registered_users'] = ($input['bypass_for_registered_users'] == 1 ? 1: 0);
            $validated['show_in_lost_password'] = ($input['show_in_lost_password'] == 1 ? 1: 0);
            $validated['show_in_login'] = ($input['show_in_login'] == 1 ? 1: 0);
            $validated['timedelta_activation'] = $input['timedelta_activation'] == 1 ? 1: 0;
            $validated['timedelta'] = $input['timedelta'] ? intval($input["timedelta"]) : 15; // use the intval filter
            $timedelta_options = array('spam','0');
            $validated['picatcha_timedelta_dropdown'] = $this->validate_dropdown($timedelta_options,'picatcha_timedelta_dropdown', $input['picatcha_timedelta_dropdown']);
            
            $validated['send_repudata'] = ($input['send_repudata'] == 1 ? 1: 0);
            
            $reputation_user_levels_options = array(0,1);
            $validated['reputation_user_levels'] = $this->validate_dropdown($reputation_user_levels_options,'reputation_user_levels',$input['reputation_user_levels']);
            //$validated['reputation_user_levels'] = ($input['reputation_user_levels'] == 1 ? 1: 0);
            
            $capabilities = array ('read', 'edit_posts', 'publish_posts', 'moderate_comments', 'activate_plugins');
            $themes = array ('red', 'white', 'blackglass', 'clean');
            
            $picatcha_languages = array ('en', 'nl', 'fr', 'de', 'pt', 'ru', 'es', 'tr', 'hi', 'hu', 'is', 'zh', 'ar', 'tl', 'it', 'vi');
            
            $validated['minimum_bypass_level'] = $this->validate_dropdown($capabilities, 'minimum_bypass_level', $input['minimum_bypass_level']);
            $validated['comments_theme'] = $this->validate_dropdown($themes, 'comments_theme', $input['comments_theme']);
            
            $validated['comments_tab_index'] = $input['comments_tab_index'] ? $input["comments_tab_index"] : 5; // use the intval filter
            
            $validated['show_in_registration'] = ($input['show_in_registration'] == 1 ? 1 : 0); // activation of timedelta
            $validated['registration_theme'] = $this->validate_dropdown($themes, 'registration_theme', $input['registration_theme']);
            $validated['registration_tab_index'] = $input['registration_tab_index'] ? $input["registration_tab_index"] : 30; // use the intval filter
            
            $validated['picatcha_language'] = $this->validate_dropdown($picatcha_languages, 'picatcha_language', $input['picatcha_language']);
            $validated['language_override'] = ($input['language_override'] == 1 ? 1 : 0); //allow users to override the language on the Picatcha form
            $validated['xhtml_compliance'] = ($input['xhtml_compliance'] == 1 ? 1 : 0);
            $validated['display_powered_by'] = 1;
            $picatcha_ssl_options = array(0,1,2); // options for SSL
            $validated['picatcha_use_ssl'] = $this->validate_dropdown($picatcha_ssl_options, 'picatcha_use_ssl', $input['picatcha_use_ssl']);
            
            $picatcha_captcha_format_options = array('1','2');
            $picatcha_image_size_options = array(50,60,75);
            $picatcha_image_noise_level_options = array(0,1,2,3,4,5,6,7,8,9,10);
            $picatcha_image_noise_type_options = array(0,1,2);
            $validated['picatcha_captcha_format'] =    $this->validate_dropdown($picatcha_captcha_format_options,    'picatcha_captcha_format',    $input['picatcha_captcha_format']);
            $validated['picatcha_image_size'] =        $this->validate_dropdown($picatcha_image_size_options,        'picatcha_image_size',        $input['picatcha_image_size']);
            $validated['picatcha_image_noise_level'] = $this->validate_dropdown($picatcha_image_noise_level_options, 'picatcha_image_noise_level', $input['picatcha_image_noise_level']);
            $validated['picatcha_image_noise_type'] =  $this->validate_dropdown($picatcha_image_noise_type_options,  'picatcha_image_noise_type',  $input['picatcha_image_noise_type']);
            $validated['picatcha_style_color'] = trim($input['picatcha_style_color']);
            
            $validated['no_response_error'] = $input['no_response_error'];
            $validated['incorrect_response_error'] = $input['incorrect_response_error'];
            
            return $validated;
        }
        
        function dumpUserGlobals(){
          //if a user opts into providing us with user data for better spam protection.
          global $user_login;
          global $user_email;
          global $user_level;
          global $current_user;
          $data = $current_user->data;
          //echo "current user";
          //var_dump($current_user);
          #$userData = "Username: ". $user_login. ", Email: ". $user_email.", User Level: ". $user_level . ", Registered since: ". $current_user->data->user_registered. ", fn: ".$current_user->data->first_name . ", ln: ". $current_user->data->last_name;
          
          //$userData = " {\"un\": \"". $user_login. "\", \"em\": \"". $user_email."\", \"ul\": \"". $user_level . "\", \"rs\": \"". $data->user_registered. "\", \"fn\": \"".$data->first_name . "\", \"ln\": \"". $data->last_name ."\", \"ts\":\"".$hour.":".$minute.":".$second."\"}";
          
          $userData = array(
            "rd" => array(
              "un" => $user_login,
              "em" => $user_email,
              "ul" => $user_level,
              "rs" => $data->user_registered,
              "fn" => $data->first_name,
              "ln" => $data->last_name,
              "ts" => getdate(),
              "cd" => array() //form data data
            )
          );
          
          
          //echo "<script type='text/javascript'>console.log('".$userData."')</script>";
          return $userData;
        }
        
        // display picatcha
        function show_picatcha_in_registration($errors) {
            $format = <<<FORMAT
            <script type='text/javascript'>
            var PicatchaOptions = { theme : '{$this->options['registration_theme']}', lang : '{$this->options['picatcha_language']}', langOverride : '{$this->options['language_override']}', tabindex : {$this->options['registration_tab_index']} };
            </script>
FORMAT;
            /*if($this->options['display_powered_by']==0){
                echo "<style>.picatcha_link{display:none;}</style>";
                $comment_string = <<<COMMENT_FORM
                
COMMENT_FORM;
            }*/
            
            
            $comment_string = <<<COMMENT_FORM
            <script type='text/javascript'>   
            document.getElementById('picatcha').style.direction = 'ltr';
            </script>
COMMENT_FORM;

            // todo: is this check necessary? look at the latest picatchalib.php
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $use_ssl = true;
            else
                $use_ssl = false;
                
            //check if rerror is set
            if(isset($_GET['rerror'])){
                $rerror = $_GET['rerror'];
            }else{
                $rerror = "";
            }

            //displays an error message in the captcha for the login screen
            if(isset($_REQUEST['picatcha'])){
                switch ($_REQUEST['picatcha']){
                    case 'empty':
                        echo "<b style='color: red;'>".$this->options['no_response_error']."</b>";
                        break;
                    case 'incorrect':
                        echo "<b style='color: red;'>".$this->options['incorrect_response_error']."</b>";
                        break;
                }
            }
            
            // if it's for wordpress mu, show the errors
            if ($this->is_multi_blog()) {
                #$error = $errors->get_error_message('picatcha');
                #echo '<label for="verification">Verification:</label>';
                #echo ($error ? '<p class="error">'.$error.'</p>' : '');
                echo $format . $this->get_picatcha_html($rerror, $use_ssl);
            }
            
            // for regular wordpress
            else {
                echo $format . $this->get_picatcha_html($rerror, $use_ssl);
            }
        }
        
        function validate_picatcha_response($errors) {
            // if the user does not fill out picatcha, throw the empty response error
            
             if (empty($_POST['picatcha']['r']) || $_POST['picatcha']['r'] == '') {
                if(is_wp_error($errors)){
                    $errors->add('blank_captcha', $this->options['no_response_error']);
                }else{
                    $errors .= $this->options['no_response_error'];
                }
                 
                 return $errors;
                 
             }
             $response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);
 
             // response is bad, add incorrect response error
             
             if (!$response->is_valid)
                 if ($response->error == 'incorrect-answer'){
                     
                 
                    if (is_wp_error($errors))
                        $errors->add('captcha_wrong', $this->options['incorrect_response_error']);
                    if (!is_wp_error($errors)){
                        
                        // works for form errors
                        $errors .= $this->options['incorrect_response_error'];
                    }
                 }
            return $errors;
        }


        function check_picatcha_lost_password(){
            if (empty($_POST['picatcha']['r']) || $_POST['picatcha']['r'] == ''){
                wp_die(__($this->options['no_response_error']. "<br/> Please click your browser&apos;s back button to try again."));
            }
            
            //validate the captcha
            $response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);
            
            if (!$response->is_valid){
                if ($response->error == 'incorrect-answer')
                    wp_die(__($this->options['incorrect_response_error']. "<br/> Please click your browser&apos;s back button to try again."));
            }
         
        }
        
        function validate_picatcha_response_redirect($url) {
            //function to validate picatcha when the user is redirecting to a page.. ie if the username and password is correct
            
            //check to see if the picatcha is filled out to begin with...
             if (empty($_POST['picatcha']['r']) || $_POST['picatcha']['r'] == '') {
                 $_SESSION['rerror']= __('picatcha', 'The Picatcha is Empty!', 'picatcha');
                 // Log the user out immediately
                 wp_logout();
                 //return them to the login screen, passing along something to tell them the picatcha was wrong...
                 return $_SERVER['REQUEST_URI']."?picatcha=empty";
             }
             
             $response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);
 
             //if it is filled out, then validate it...
             
             if (!$response->is_valid){
                 //echo "response is not valid";
                 if ($response->error == 'incorrect-answer')
                    if (!is_wp_error($errors)){
                        // works for form errors
                        //$url = '?picatcha='.$this->options['no_response_error'];
                        //return $url;
                        $PicatchaError = new WP_Error('Picatcha',__('Picatcha is wrong'));
                        return $_SERVER['REQUEST_URI']."?picatcha=incorrect";
                        
                    }
                    //if the user did not pass picatcha, logs them out immediately 
                    wp_logout();
             }else{
                //user completed the captcha correctly
                
                //return them to the main site
                return site_url()."/wp-admin/"; //maybe add in an option to the preferences to redirect it to another page? say the root page or something else?
            }
        }
        
        function bph_picatcha(){
          // generates the captcha on the buddypress signup page
          // possibly a more elegant way to do this...
          $picatchaOptions =<<<PICATCHA_OPTIONS
            <script type='text/javascript'>
            var PicatchaOptions = { theme : '{$this->options['registration_theme']}', lang : '{$this->options['picatcha_language']}', langOverride : '{$this->options['language_override']}', tabindex : {$this->options['registration_tab_index']} };</script>
            <style type="text/css">#picatcha{clear:both;}</style>
            <label style="clear:both;">Pix-Captcha</lable>
PICATCHA_OPTIONS;
          echo $picatchaOptions;
          echo "<div class='error' style='clear:both;width:48%;'>";
          echo do_action('bp_bph_field_errors');
          echo "</div>";
          echo $this->get_picatcha_html($bp->signup->errors['bph_field'], $use_ssl);
        }
        
        function picatcha_check_bp(){
          // Checks if the pixcaptcha is correct on buddypress
          global $bp;
          
          // Check if the CAPTCHA is empty
          if (empty($_POST['picatcha']['r']) || $_POST['picatcha']['r'] == '') {
            $bp->signup->errors['bph_field'] = __($this->options['no_response_error'], 'buddypress');
          }else{
            // If filled out, check if the answer is correct or not
            $response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_POST['picatcha']['token'], $_POST['picatcha']['r']);

             // response is bad, add incorrect response error
             if (!$response->is_valid)
                 if ($response->error == 'incorrect-answer'){
                     $bp->signup->errors['bph_field'] = __($this->options['incorrect_response_error'], 'picatcha');
                 }
          }
          return;
        }
        
        
        function validate_picatcha_response_wpmu($result) {
            // must make a check here, otherwise the wp-admin/user-new.php script will keep trying to call
            // this function despite not having called do_action('signup_extra_fields'), so the picatcha
            // field was never shown. this way it won't validate if it's called in the admin interface
            
            if (!$this->is_authority()) {
                // blogname in 2.6, blog_id prior to that
                // todo: why is this done?
                if (isset($_POST['blog_id']) || isset($_POST['blogname']))
                    return $result;
                    
                // no text entered
                if (empty($_POST['picatcha']['r']) || $_POST['picatcha']['r'] == '') {
                    echo '<div class="error">' . $this->options['no_response_error'] . '</div>';
                    $result['errors']->add('blank_picatcha', $this->options['no_response_error']);
                    return $result;
                }
                
                $response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'],$_POST['picatcha']['token'], $_POST['picatcha']['r']);
                
                // response is bad, add incorrect response error
                // todo: why echo the error here? wpmu specific?
                if (!$response->is_valid)
                    if ($response->error == 'incorrect-answer') {
                        $result['errors']->add('picatcha_wrong', $this->options['incorrect_response_error']);
                        echo '<div class="error">' . $this->options['incorrect_response_error'] . '</div>';
                    }
                    
                return $result;
            }
        }
        
        // utility methods
        function hash_comment($id) {
            define ("PICATCHA_WP_HASH_SALT", "b7e0638d85f5d7f3694f68e944136d62");
            
            if (function_exists('wp_hash'))
                return wp_hash(PICATCHA_WP_HASH_SALT . $id);
            else
                return md5(PICATCHA_WP_HASH_SALT . $this->options['private_key'] . $id);
        }
        
        function get_picatcha_html($picatcha_error, $use_ssl=false) {
            $error = null;
            if ($picatcha_error) {
                if ($picatcha_error == 'incorrect-answer') {
                    $error = $this->options['incorrect_response_error'];
                } else {
                    $error = "Error: " . $picatcha_error;
                }
            }
            //if the user is overriding the automatic detection of SSL, do so here
            if($this->options['picatcha_use_ssl']==1){
              $use_ssl=true;
            }else if($this->options['picatcha_use_ssl']==2){
              $use_ssl=false;
            }
            
            // add in the style changes here
            //$format='2', $style='#2a1f19', $link = '1', $IMG_SIZE = '75', $NOISE_LEVEL = 0, $NOISE_TYPE = 0
            return picatcha_get_html($this->options['public_key'], $error, $this->options['picatcha_captcha_format'], '#'. $this->options['picatcha_style_color'],$this->options['display_powered_by'], $this->options['picatcha_image_size'], $this->options['picatcha_image_noise_level'], $this->options['picatcha_image_noise_type'], $use_ssl, $this->options['picatcha_language'], $this->options['language_override']); 
        }
        
        function show_picatcha_in_comments() {
            //$this->dumpUserGlobals();
            global $user_ID;

            // set the minimum capability needed to skip the captcha if there is one
            if (isset($this->options['bypass_for_registered_users']) && $this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];

            // skip the Picatcha display if the minimum capability is met
            if ((isset($needed_capability) && $needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return;

            else {
                // Did the user fail to match the CAPTCHA? If so, let them know
                if ((isset($_GET['rerror']) && $_GET['rerror'] == 'incorrect-captcha-sol'))
                    echo '<p class="picatcha-error">' . $this->options['incorrect_response_error'] . "</p>";

                //timestamp
                echo "<input type='hidden' readonly='readonly' name='picatchaTimestamp' value='".time()."'/>";
                //echo "allocated time: " .$this->options['timedelta']."<br />";

                //modify the comment form for the Picatcha widget
                $picatcha_js_opts = <<<OPTS
                <script type='text/javascript'>
                    var PicatchaOptions = { theme : '{$this->options['comments_theme']}', lang : '{$this->options['picatcha_language']}' , langOverride : '{$this->options['language_override']}', tabindex : {$this->options['comments_tab_index']} };
                </script>
OPTS;

                // todo: replace this with jquery: http://digwp.com/2009/06/including-jquery-in-wordpress-the-right-way/
                // todo: use math to increment+1 the submit button based on what the tab_index option is
                if ($this->options['xhtml_compliance']) {
                    $comment_string = <<<COMMENT_FORM
                        <div id="picatcha-submit-btn-area">&nbsp;</div>
COMMENT_FORM;
                }

                else {
                    $comment_string = <<<COMMENT_FORM
                        <div id="picatcha-submit-btn-area">&nbsp;</div>
                        <noscript>
                         <style type='text/css'>#submit {display:none;}</style>
                         <input name="submit" type="submit" id="submit-alt" tabindex="6" value="Submit Comment"/> 
                        </noscript>
COMMENT_FORM;
                }

                $use_ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");

                echo $picatcha_js_opts . $this->get_picatcha_html(isset($_GET['rerror']) ? $_GET['rerror'] : null, $use_ssl) . $comment_string;
                //echo "display Powered by:".$this->options['display_powered_by'];
                /*if($this->options['display_powered_by']==0){
                    echo "<style>.picatcha_link{display:none; .picatchaRefreshButton{margin-left:280px ! important;}</style>";
                }*/
           }
        }
        
        // this is what does the submit-button re-ordering
        function save_comment_script() {
            $javascript = <<<JS
                <script type="text/javascript">
                var sub = document.getElementById('submit');
                if (document.getElementById('picatcha-submit-btn-area')){
                    document.getElementById('picatcha-submit-btn-area').appendChild (sub);
                    document.getElementById('submit').tabIndex = 6;
                    if ( typeof _picatcha_wordpress_savedcomment != 'undefined') {
                            document.getElementById('comment').value = _picatcha_wordpress_savedcomment;
                    }
                }
                //document.getElementById('picatcha_table').style.direction = 'ltr';
                </script>
JS;
            echo $javascript;
        }
        
        // todo: this doesn't seem necessary
        function show_captcha_for_comment() {
            global $user_ID;
            return true;
        }
        
        function check_comment($comment_data) {
            global $user_ID;
            
            if ($this->options['bypass_for_registered_users'] && $this->options['minimum_bypass_level'])
                $needed_capability = $this->options['minimum_bypass_level'];
            
            if (($needed_capability && current_user_can($needed_capability)) || !$this->options['show_in_comments'])
                return $comment_data;
                
            //checks the timestamp
            //die("time: ". intval(time()) . " time from post: " . intval($_POST['picatchaTimestamp']). " timedelta: " . intval(time())-intval($_POST['picatchaTimestamp']));
            
            /*time Delta logic*/
            if ($this->options['timedelta_activation'] && (intval(time())-intval($_POST['picatchaTimestamp'])) < intval($this->options['timedelta'])){
              //add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
              add_filter('pre_comment_approved', create_function('$a', 'return '. $this->options["picatcha_timedelta_dropdown"].';'));
              return $comment_data;
            }
            
            if ($this->show_captcha_for_comment()) {
                // do not check trackbacks/pingbacks
                if ($comment_data['comment_type'] == '') {
                  
                  //reputation data
                  //$repu_data = $this->dumpUserGlobals();
                  //$repu_data["rd"]["cd"]=$comment_data;
                  $repu_data=array();
                  //picatcha_reputation_analysis($repu_data);
                  $challenge = $_POST['picatcha']['token'];
                  $response = $_POST['picatcha']['r'];
                  //$repu_data=null;

                  $picatcha_response = picatcha_check_answer($this->options['private_key'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'],$challenge, $response, $repu_data);
                  if ($picatcha_response->is_valid)
                    return $comment_data;
                  else {
                    $this->saved_error = $picatcha_response->error;
                    // http://codex.wordpress.org/Plugin_API/Filter_Reference#Database_Writes_2
                    add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'));
                    return $comment_data;
                  }
                }
            }
            
            return $comment_data;
        }
        
        function relative_redirect($location, $comment) {
            if ($this->saved_error != '') {
                // replace #comment- at the end of $location with #commentform
                
                $location = substr($location, 0, strpos($location, '#')) .
                    ((strpos($location, "?") === false) ? "?" : "&") .
                    'rcommentid=' . $comment->comment_ID .
                    '&rerror=' . $this->saved_error .
                    '&rchash=' . $this->hash_comment($comment->comment_ID) .
                    '#commentform';
            }
            
            return $location;
        }
        
        function saved_comment() {
            if (!is_single() && !is_page())
                return;
            
            //checks to see if the variables are set, otherwise defaults them to null
            $comment_id = "";
            $comment_hash = "";
            if(isset($_REQUEST['rcommentid']))
                $comment_id = $_REQUEST['rcommentid'];
            
            if (isset($_REQUEST['rchash']))
                $comment_hash = $_REQUEST['rchash'];
            
            //$comment_id = $_REQUEST['rcommentid'];
            //$comment_hash = $_REQUEST['rchash'];
            
            if (empty($comment_id) || empty($comment_hash))
               return;
            
            if ($comment_hash == $this->hash_comment($comment_id)) {
               $comment = get_comment($comment_id);

               // todo: removed double quote from list of 'dangerous characters'
               $com = preg_replace('/([\\/\(\)\+\;\'])/e','\'%\'.dechex(ord(\'$1\'))', $comment->comment_content);
                
               $com = preg_replace('/\\r\\n/m', '\\\n', $com);
                
               echo "
                <script type='text/javascript'>
                var _picatcha_wordpress_savedcomment =  '" . $com  ."';
                _picatcha_wordpress_savedcomment = unescape(_picatcha_wordpress_savedcomment);
                </script>
                ";

                wp_delete_comment($comment->comment_ID);
            }
        }
        
        // todo: is this still needed?
        // this is used for the api keys url in the administration interface
        function blog_domain() {
            $uri = parse_url(get_option('siteurl'));
            return $uri['host'];
        }
        
        // add a settings link to the plugin in the plugin list
        function show_settings_link($links, $file) {
            if ($file == plugin_basename($this->path_to_plugin_directory() . '/wp-picatcha.php')) {
               $settings_title = __('Settings for this Plugin', 'picatcha');
               $settings = __('Settings', 'picatcha');
               $settings_link = '<a href="options-general.php?page=picatcha/picatcha.php" title="' . $settings_title . '">' . $settings . '</a>';
               array_unshift($links, $settings_link);
            }
            
            return $links;
        }
        
        // add the settings page
        function add_settings_page() {
            // add the options page
            if ($this->environment == Environment::WordPressMU && $this->is_authority())
                add_submenu_page('wpmu-admin.php', 'WP-Picatcha', 'WP-Picatcha', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));

            if ($this->environment == Environment::WordPressMS && $this->is_authority())
                add_submenu_page('ms-admin.php', 'WP-Picatcha', 'WP-Picatcha', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));
            
            add_options_page('WP-Picatcha', 'WP-Picatcha', 'manage_options', __FILE__, array(&$this, 'show_settings_page'));
        }
        
        // store the xhtml in a separate file and use include on it
        function show_settings_page() {
            include("picatcha-settings.php");
        }
        
        function build_dropdown($name, $keyvalue, $checked_value) {
            echo '<select name="' . $name . '" id="' . $name . '">' . "\n";
            
            foreach ($keyvalue as $key => $value) {
                $checked = ($value == $checked_value) ? ' selected="selected" ' : '';
                
                echo '\t <option value="' . $value . '"' . $checked . ">$key</option> \n";
                $checked = NULL;
            }
            
            echo "</select> \n";
        }
        
        function capabilities_dropdown() {
            // define choices: Display text => permission slug
            $capabilities = array (
                __('all registered users', 'picatcha') => 'read',
                __('edit posts', 'picatcha') => 'edit_posts',
                __('publish posts', 'picatcha') => 'publish_posts',
                __('moderate comments', 'picatcha') => 'moderate_comments',
                __('activate plugins', 'picatcha') => 'activate_plugins'
            );
            
            $this->build_dropdown('picatcha_options[minimum_bypass_level]', $capabilities, $this->options['minimum_bypass_level']);
        }
        
        function reputation_user_levels() {
            // choices to send data based on comments for logged in and anonymous users
            $capabilities = array (
                __('Anonymous Users', 'picatcha') => 0,
                __('Anonymous and Registered', 'picatcha') => 1
            );
            
            $this->build_dropdown('picatcha_options[reputation_user_levels]', $capabilities, $this->options['reputation_user_levels']);
        }
        
        function theme_dropdown($which) {
            $themes = array (
                __('Regular', 'picatcha') => 'Regular'
//                __('White', 'picatcha') => 'white',
//                __('Black Glass', 'picatcha') => 'blackglass',
//                __('Clean', 'picatcha') => 'clean'
            );
            
            if ($which == 'comments')
                $this->build_dropdown('picatcha_options[comments_theme]', $themes, $this->options['comments_theme']);
            else if ($which == 'registration')
                $this->build_dropdown('picatcha_options[registration_theme]', $themes, $this->options['registration_theme']);
        }
        
        
        function picatcha_language_dropdown() {
            $languages = array (
                __('English', 'picatcha') => 'en',
                __('Dutch', 'picatcha') => 'nl',
                __('French', 'picatcha') => 'fr',
                __('German', 'picatcha') => 'de',
                __('Portuguese', 'picatcha') => 'pt',
                __('Russian', 'picatcha') => 'ru',
                __('Spanish', 'picatcha') => 'es',
                __('Turkish', 'picatcha') => 'tr',
                __('Hindi', 'picatcha') => 'hi',
                __('Hungarian', 'picatcha') => 'hu',
                __('Icelandic', 'picatcha') => 'is',
                __('Chinese', 'picatcha') => 'zh',
                __('Arabic', 'picatcha') => 'ar',
                __('Filipino' , 'picatcha') => 'tl',
                __('Italian', 'picatcha') => 'it',
                __('Vietnamese', 'picatcha') => 'vi',
                __('Slovak', 'picatcha') => 'sk'
            );
            
            $this->build_dropdown('picatcha_options[picatcha_language]', $languages, $this->options['picatcha_language']);
        }
        
        function picatcha_timedelta_dropdown() {
            $timeDeltaOpts = array (
                __('Spam', 'picatcha') => 'spam',
                __('Moderation', 'picatcha') => '0'
            );
            
            $this->build_dropdown('picatcha_options[picatcha_timedelta_dropdown]', $timeDeltaOpts, $this->options['picatcha_timedelta_dropdown']);
        }
        
        function picatcha_captcha_format(){
          $format_options = array (
            __('3 x 2', 'picatcha') => '1',
            __('4 x 2', 'picatcha') => '2'
          );
          
          $this->build_dropdown('picatcha_options[picatcha_captcha_format]', $format_options, $this->options['picatcha_captcha_format']);
        }
        
        function picatcha_image_size(){
          $image_size = array(
            __('50', 'picatcha') =>50,
            __('60', 'picatcha') =>60,
            __('75', 'picatcha') =>75
          );
          
          $this->build_dropdown('picatcha_options[picatcha_image_size]', $image_size, $this->options['picatcha_image_size']);
        }
        
        function picatcha_image_noise_level(){
          $noise_level = array(
            __('Off', 'picatcha') =>0,
            __('1 - A Little', 'picatcha') =>1,
            __('2', 'picatcha') =>2,
            __('3', 'picatcha') =>3,
            __('4', 'picatcha') =>4,
            __('5 - Moderate', 'picatcha') =>5,
            __('6', 'picatcha') =>6,
            __('7', 'picatcha') =>7,
            __('8', 'picatcha') =>8,
            __('9', 'picatcha') =>9,
            __('10 - Maximum!', 'picatcha') =>10
          );
          
          $this->build_dropdown('picatcha_options[picatcha_image_noise_level]', $noise_level, $this->options['picatcha_image_noise_level']);
        }
        function picatcha_image_noise_type(){
          $noise_type = array(
            __('Random', 'picatcha') =>0,
            __('Shadow', 'picatcha') =>1,
            __('Pixelation', 'picatcha') =>2
          );
          
          $this->build_dropdown('picatcha_options[picatcha_image_noise_type]', $noise_type, $this->options['picatcha_image_noise_type']);
        }
        
        function picatcha_use_ssl(){
          $use_ssl = array(
            __('Auto Detect', 'picatcha') => 0,
            __('Use HTTPS (a secure connection)', 'picatcha') => 1,
            __('Use HTTP (a non secure connection)', 'picatcha') => 2
          );
          $this->build_dropdown('picatcha_options[picatcha_use_ssl]', $use_ssl, $this->options['picatcha_use_ssl']);
        }


        
    } // end class declaration
} // end of class exists clause

?>
