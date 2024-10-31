<?php
/*
Plugin Name: Picatcha
Plugin URI: http://www.picatcha.com/
Description:  Usable and Secure CAPTCHAs. The challenges are easy for humans to solve (results in high conversions) and at the same time hard for spam bots. This ensures a great user-experience for your website visitors.
Version: 1.3.3
Author: Picatcha Inc
Email: contact@picatcha.net
Author URI: http://www.picatcha.com
*/

// this is the 'driver' file that instantiates the objects and registers every hook

define('ALLOW_INCLUDE', true);

require_once('picatcha.php');

$picatcha = new Picatcha('picatcha_options');

?>
