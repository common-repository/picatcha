=== Plugin Name ===
Contributors: Picatcha Inc
Website: http://picatcha.com
Tags: comments, registration, picatcha, antispam, captcha, BuddyPress, forgot password, image captcha, anti-spam, image identification captcha
Requires at least: 2.7.1
Tested up to: 3.2.1
Stable tag: 1.3.3

Integrates Picatcha image-identification CAPTCHA - anti-spam solution with WordPress including comment and registration.

== Description ==

= What is Picatcha? =

Picatcha(TM) is a unique image-identification CAPTCHA that effectively protects your website from Internet abuse - spam and automated bots. It is a well known fact that websites lose approximately 3-18% of user interactions (comments, sign-up etc) due to the additional burden of re-typing the squiggly garbled text CAPTCHAs. Imagine if your website is visited from a tablet device or a smartphone - the end user is left to zoom/pan, decipher the text and typing with auto-correction makes the whole experience frustrating. It is also inevitable that mobile Internet will eclipse desktop Internet, and we believe that as a website administrator you make the right CAPTCHA choice and be ready for this shift. 

Hence, Picatcha(TM) was designed keeping all these factors in mind -  to give the highest levels of user experience to your website visitors while ensuring the strongest defense against spam on all kinds of devices. Picatcha(TM) presents the website visitor with an array of thumbnails. The visitor clicks and selects them to verify that he/she is a human interacting with your website and not an automated bot. Pix-Catpcha(TM) changes the standard CAPTCHA test of "are you a human?" from a necessary evil to a positive experience. 

Picatcha(TM) is a FREE web service developed initially at UC Berkeley, USA by a team of students and professors with research focus in user-interface design, web security and system design. You may enable it to stop spam on sign-up forms, registration pages, comments, polls and many more. We developed plugins for recent versions of Wordpress (2.7 and later) for your convenience. Please mail to contact[at]picatcha[dot]com if you need integration support or any form of internal customization. 

Sign Up on www.picatcha.com to get the Public and Private Keys to enable the plugin. 

Enable Picatcha - Stop Spam - Delight your website visitors!


Watch our introduction/overview video:
[youtube http://www.youtube.com/watch?v=BOi9jWN2iR8] 

== Installation ==

To install in regular WordPress:

1. Upload the `picatcha` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the `Plugins` menu in WordPress
3. Get the pix-captcha keys (Public and Private) [here](http://www.picatcha.com/signup)
4. Add the Public/Private keys in Settings menu


== Requirements ==

* You need the public and private keys 
* Your theme must have a `do_action('comment_form', $post->ID);` call right before the end of your form (*Right before the closing form tag*). Most themes do.

== ChangeLog ==

= Version 1.3.3 =
* Added localization option for the Picatcha CAPTCHA: Hungarian

= Version 1.3.2 =
* Added SSL capabilities: The Picatcha Plugin will try to detect if your WordPress installation is using https or not. You can also manually set it to use https or http via the Picatcha settings page.

= Version 1.3.1 =
* Added BuddyPress registration page compatibility

= Version 1.3 =
* Name Change: We are renaming our image CAPTCHA from Picatcha to Pix-Captcha. We plan to build more anti-spam and anti-internet-abuse software and services. The module name will stay the same, but in the settings, the CAPTCHA itself is now referred to as Pix-Captcha.
* TimeDelta Comment Protection: TimeDelta introduces a minimum time that the user must view your post before their comment can be posted legitimately. Many spam bots will try to load your post and comment immediately. If you set the TimeDelta to 15 seconds, any comments posted within 15 seconds of loading the page can either be sent to moderation or to the spam bucket. TimeDelta currently requires Pix-Captcha to be active for comments in order to run
* Picatcha Customizations: We have updated the Picatcha widget to allow more customization. You can select the number of images, color styling, image size, and optional image noise. You can play around with these values on a live version at [picatcha.com/custom/](http://www.picatcha.com/custom/).
* Public key / Private Key Validator: We have included a key tester, to let you check the keys to make sure they're correct. Clicking on the Validate Key button will check your keys against our server, and will return a green 'valid' if the key is inputted correctly, and a red 'invalid!' if not.
* Redesigned settings page: We redesigned the settings page to make it more compact and usable. We also removed some vestigial settings that were probably not used.
* Wordpress Network update: fixed a bug that would not properly retrieve the settings page, along with debugging the functionality for Wordpress Network installations.


= Version 1.2.1 = 
* Minor CSS updates 

= Version 1.2 =
* PICATCHA now supports language translations. From the PICATCHA settings page, you can select a default language for the question to be presented in. You can also allow users to override the default language by selecting to "Allow user to override the default PICATCHA language". This option adds a selection menu to pick the language of their choice.

* PICATCHA supports the following languages: 
    * English
    * Dutch
    * French
    * German
    * Portuguese
    * Russian
    * Spanish
    * Turkish
    * Hindi
    * Icelandic
    * Chinese
    * Arabic
    * Filipino
    * Italian
    * Vietnamese
    * Slovak

* Currently the questions and categories are machine translated. If you would like to provide a better (or grammatically correct translation) or request another language, email us at contact@picatcha.com

= Version 1.1 =
* Added PICATCHA to the login and lost password screens.

= Version 1.01 =
* Fixed a bug that prevented users from accessing the settings page

= Version 1.0 =
* Alpha product release

== Upgrade Notice ==

= Version 1.2.1 =
This update should fix an issue with the refresh button being pushed too far off to the right on the screen.

= Version 1.2 =
Version 1.2 adds support for 16 languages in PICATCHA.

= Version 1.1 =
Version 1.1 allows Wordpress admins to have users solve PICATCHAs on the login screen and lost password screen, in addition to previous bug fixes.

== Frequently Asked Questions ==

1. Moderation Emails: Picatcha(TM) marks comments from users who do not pass the Picatcha(TM) test as spam, so they will still appear in the spam category of the comments section. 

2. Picatcha(TM) cannot do anything about pingback and trackbacks. Those can be disabled in Options > Discussion > Allow notification from other Weblogs (Pingbacks and trackbacks).

3. Where can I get the public/private keys? Keys are available from PICATCHA's website - www.picatcha.com - once you register, you will get the keys to start using Picatcha(TM) 

4. How does Picatcha(TM) compare to other CAPTCHA systems such as reCAPTCHA? Picatcha(TM) uses image recognition, which is easy for humans to do, but still very difficult for machines. With text based CAPTCHA systems, computers can undo the image transformations which make the text difficult to read, and then match the shapes of characters with Optical Character Recognition. Image recognition is still more difficult to do, especially when noise or other transformations happen on the image. 

== Screenshots ==

1. The Picatcha Settings

2. Picatcha in a comment form scaled down 50%

3. Picatcha on the registration form of Wordpress

4. Picatcha on the login form of Wordpress

5. Picatcha default language select and option to allow user to override
