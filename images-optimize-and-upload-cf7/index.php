<?php
/**
 * Plugin Name: Images Optimize and Upload CF7
 * Description: A simple plugin that automatically optimizes, resizes & uploads images in Contact Form 7 plugin forms on the client side. You get nice and compressed images.
 * Author: Damir Akhmedshin and Rustam Sibagatov
 * Author URI: https://github.com/yesworld
 * Domain Path: /languages
 * License: GPL2
 * Version: 2.2.1
 */
define('YR3K_UPLOAD_VERSION', '2.2.1');
define('YR3K_UPLOAD_REQUIRED_WP_VERSION', '4.9');
define('YR3K_UPLOAD_BASENAME', plugin_basename(__FILE__));
define('YR3K_UPLOAD_PATH', plugin_dir_path(__FILE__));
define('YR3K_UPLOAD_REGISTRATION_NAME', dirname(YR3K_UPLOAD_BASENAME));
define('YR3K_UPLOAD_SHORTCODE', 'upload_image');
define('YR3K_UPLOAD_FILE_FORMATS', get_option('yr-images-optimize-upload-file-formats', 'png|jpg|jpeg|gif|bmp'));

$upload_dir = wp_upload_dir();
define('YR3K_UPLOAD_TEMP_DIR', path_join($upload_dir['basedir'], 'wpcf7_upload_image'));
define('YR3K_UPLOAD_BASEURL', path_join($upload_dir['baseurl'], 'wpcf7_upload_image'));

// Array of error message.
define('YR3K_UPLOAD_ERRORS', [
    'failed_upload' => __('There was an error uploading the file. Please contact the website administrator.', YR3K_UPLOAD_REGISTRATION_NAME),
    'incorrect_type' => __('You are not allowed to upload files of this type.', YR3K_UPLOAD_REGISTRATION_NAME),
]);

require_once YR3K_UPLOAD_PATH.'admin/settings.php';
require_once YR3K_UPLOAD_PATH.'admin/ajax.php';

new Yr3kUploaderApi();
new Yr3kUploaderSettings();

if (is_admin()) {
    require_once YR3K_UPLOAD_PATH.'admin/index.php';
    new Yr3kUploaderAdmin();
    new Yr3kUploaderButtonTagCF7();
} else {
    require_once YR3K_UPLOAD_PATH.'frontend/index.php';
    new Yr3kUploaderFrontend();
}
