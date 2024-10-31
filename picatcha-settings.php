<?php

    if (defined('ALLOW_INCLUDE') === false)
        die('no direct access');

?>
<?php echo '<script type="text/javascript" src="http://' . PICATCHA_API_SERVER . '/static/client/jquery.min.js"></script>';?>
<script type="text/javascript" src="http://<?php echo PICATCHA_API_SERVER?>/static/client/picatcha.js"></script>


<div class="wrap">
   <a name="Picatcha"></a>
   <h2><?php _e('Picatcha Options', 'picatcha'); ?></h2>
   <p><?php _e('Picatcha is a free, accessible CAPTCHA service which is very user friendly and also helps to stop spam on your blog .', 'picatcha'); ?></p>
   
   <form method="post" action="options.php">
      <?php settings_fields('picatcha_options_group'); ?>

      <h3><?php _e('Authentication', 'picatcha'); ?></h3>
      <p><?php _e('These keys are required before you are able to do anything else.', 'picatcha'); ?> <?php _e('You can get the keys', 'picatcha'); ?> <a href="<?php echo picatcha_get_signup_url($this->blog_domain(), 'wordpress');?>" title="<?php _e('Get your Picatcha API Keys', 'picatcha'); ?>"><?php _e('here', 'picatcha'); ?></a>.</p>
      <p><?php _e('Be sure not to mix them up! The public and private keys are not interchangeable! You can use the "Validate Keys" to double check that you entered your keys properly. Be sure to click Save after you enter your keys.'); ?></p>
      
      <table class="form-table">
         <tr valign="top">
            <th scope="row"><?php _e('Public Key', 'picatcha'); ?></th>
            <td>
               <input type="text" id="picatchaPublicKey" name="picatcha_options[public_key]" size="40" value="<?php echo $this->options['public_key']; ?>" xonblur="validateKey(this.value, 'pub')" /><span id="validpubKey"></span>
            </td>
         </tr>
         <tr valign="top">
            <th scope="row"><?php _e('Private Key', 'picatcha'); ?></th>
            <td>
               <input type="text" id="picatchaPrivateKey" name="picatcha_options[private_key]" size="40" value="<?php echo $this->options['private_key']; ?>" xonblur="validateKey(this.value, 'pri')"/><span id="validpriKey"></span>
            </td>
         </tr>
         <tr>
            <td>&nbsp;</td>
            <td><input type="button" onclick="Picatcha.wpCheckKeysBtn()" value="Validate Keys"></td>
         </tr>
      </table>
      <p class="submit"><input type="submit" class="button-primary" title="<?php _e('Save Options') ?>" value="<?php _e('Save Changes') ?> &raquo;" /></p>
      
      
      <h3>Pix-Captcha Options</h3>
      <table width="100%">
        <tr>
          <td colspan="2"><h5 style="border-bottom:1px dotted #666">Activate for:</h5></td>
        </tr>
        
        <!-- show in comments, enable for registration -->
        <tr>
          <td><input type="checkbox" id ="picatcha_options[show_in_comments]" name="picatcha_options[show_in_comments]" value="1" <?php checked('1', $this->options['show_in_comments']); ?> />
           <label for="picatcha_options[show_in_comments]"><?php _e('Enable for comments form', 'picatcha'); ?></label>
          </td>
          <td><input type="checkbox" id ="picatcha_options[show_in_registration]" name="picatcha_options[show_in_registration]" value="1" <?php checked('1', $this->options['show_in_registration']); ?> />
            <label for="picatcha_options[show_in_registration]"><?php _e('Enable for registration form', 'picatcha'); ?></label></td>
        </tr>
        
        <!-- show in login, lost password -->
        <tr>
          <td><input type="checkbox" id ="picatcha_options[show_in_login]" name="picatcha_options[show_in_login]" value="1" <?php checked('1', $this->options['show_in_login']); ?> />
           <label for="picatcha_options[show_in_login]"><?php _e('Enable for login', 'picatcha'); ?></label></td>
          <td>
            <input type="checkbox" id ="picatcha_options[show_in_lost_password]" name="picatcha_options[show_in_lost_password]" value="1" <?php checked('1', $this->options['show_in_lost_password']); ?> />
            <label for="picatcha_options[show_in_lost_password]"><?php _e('Enable for lost password form', 'picatcha'); ?></label>
            </td>
        </tr>
        <tr><td colspan='2'> <br /></td></tr>
        <tr>
          <td colspan="2"><h5 style="border-bottom:1px dotted #666">Presentation Options:</h5></td>
        </tr>
        
        <!-- Default Localization, Image Size -->
        <tr>
          <td><label for="picatcha_options[picatcha_language]"><?php _e('Default Localization:', 'picatcha'); ?></label>
           <?php $this->picatcha_language_dropdown(); ?></td>
          <td> <label for="picatcha_options[picatcha_image_size]"><?php _e('Image Size','picatcha');?></label> <?php $this->picatcha_image_size();?><?php _e('px','picatcha');?></td>
        </tr>
        
        <!-- localization override, image noise level -->
        <tr>
          <td><input type="checkbox" id ="picatcha_options[language_override]" name="picatcha_options[language_override]" value="1" <?php checked('1', $this->options['language_override']); ?> />
           <label for="picatcha_options[language_override]"><?php _e('Allow users to override the default localization.', 'picatcha'); ?></label></td>
          <td><label for="picatcha_options[picatcha_image_noise_level]"><?php _e('Image Noise Level')?></label> <?php $this->picatcha_image_noise_level();?></td>
        </tr>
        
        <!-- hide for registered users, theme color -->
        <tr>
          <td><input type="checkbox" id="picatcha_options[bypass_for_registered_users]" name="picatcha_options[bypass_for_registered_users]" value="1" <?php checked('1', $this->options['bypass_for_registered_users']); ?> />
         <label for="picatcha_options[bypass_for_registered_users]"><?php _e('Hide for registered Users who can', 'picatcha'); ?></label><br />
         <?php $this->capabilities_dropdown(); ?></td>
          <td>
            <label for="picatcha_options[picatcha_style_color]"><?php _e('Theme Color')?></label>
            <label for="picatcha_options[picatcha_style_color]"><?php _e('Hex: #');?>
            <input type="text" id="picatcha_options[picatcha_style_color]" name="picatcha_options[picatcha_style_color]" maxlength="6" size="6" value="<?php echo $this->options['picatcha_style_color']; ?>" />
          </label></td>
        </tr>
        
        <!-- grid format, image noise type -->
        <tr>
          <td><label for="picatcha_options[picatcha_captcha_format]"><?php _e('Format of CAPTCHA grid', 'picatcha');?></label> <?php $this->picatcha_captcha_format();?></td>
          <td> <label for="picatcha_options[piatcha_images_noise_type]"><?php _e('Image Noise Type')?></label> <?php $this->picatcha_image_noise_type();?></td>
        </tr>    
      </table>
      
      <!-- TimeDelta Settings -->
      <h3>TimeDelta</h3>
      <table width="100%">
        <tr>
          <td colspan='2'>TimeDelta protects your comments by requiring your users wait a specified amount of time before they can post. Bots will not bother to read your blog before they spam.
          </td>
        </tr>
        <tr><td></td><td></td></tr>
        <tr>
          <td width="56%">
            <input type="checkbox" id="picatcha_options[timedelta_activation]" name="picatcha_options[timedelta_activation]" value="1" <?php checked('1', $this->options['timedelta_activation']);  ?>/> <?php _e('Activate', 'picatcha'); ?>
          </td>
          <td><?php _e('Require ','picatcha');?><input type="text" name="picatcha_options[timedelta]" size="3" value="<?php echo $this->options['timedelta']; ?>" /> <?php _e('second(s) before posting','picatcha');?></td>
        </tr>
        
        <tr>
          <td colspan="2"><?php _e('Direct comments caught by TimeDelta to:', 'picatcha')?> <?php $this->picatcha_timedelta_dropdown() ?>
          </td>
        </tr>
      </table>
      
      <h3><?php _e('HTTPS support', 'picatcha'); ?></h3>
      <table class="form-table">
        <tr valign="top">
          <th scope="row"><?php _e('Use Secure servers:')?>
            
          </th>
          <td>
            <?php $this->picatcha_use_ssl(); ?>
          </td>
        </tr>
        
      </table>
      
      <h3><?php _e('Error Messages', 'picatcha'); ?></h3>
      <table class="form-table">
         <tr valign="top">
            <th scope="row"><?php _e('Picatcha Ignored', 'picatcha'); ?></th>
            <td>
               <input type="text" name="picatcha_options[no_response_error]" size="70" value="<?php echo $this->options['no_response_error']; ?>" />
            </td>
         </tr>
         
         <tr valign="top">
            <th scope="row"><?php _e('Incorrect Guess', 'picatcha'); ?></th>
            <td>
               <input type="text" name="picatcha_options[incorrect_response_error]" size="70" value="<?php echo $this->options['incorrect_response_error']; ?>" />
            </td>
         </tr>
      </table>

      <p class="submit"><input type="submit" class="button-primary" title="<?php _e('Save Options') ?>" value="<?php _e('Save Changes') ?> &raquo;" /></p>
   </form>
   
   <?php do_settings_sections('picatcha_options_page'); ?>
</div>
<script type="text/javascript">Picatcha.wordpressHelper()</script>