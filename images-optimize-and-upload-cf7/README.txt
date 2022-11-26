=== Images Optimize and Upload CF7 ===
Contributors: yesworld, bruklig
Tags: image, compression, optimization, contact form 7, ajax uploader, drag and drop, multiple file, upload, contact form 7 uploader
Requires at least: 5.2.2
Tested up to: 6.1.1
Stable tag: 2.1.4
Requires PHP: 5.2
License: GNU GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Images Optimize and Upload CF7 is an extension plugin for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) plugin, that allows you to upload multiple images using drag-n-drop or simple "Browse" button, and compress them on the client's side before sending it. If you want to save a user's time and bandwidth on uploading large images, this plugin is perfect solution.

= Features =

* Quick compression on the client's side
* Save data by compressing it on the client's side before sending to the server
* Automatically resize images to max 1920px (width or height)
* Maintains the aspect ratio of the images
* Fix image rotation issue when uploading images from Android an iOS (uses EXIF data)
* Highly customizable
* File type validation
* No limits on input file size
* Ajax upload
* Drag-n-drop or browse file
* Attach compressed files to emails
* Adapted to mobile design
* Customize file upload thumbnails and drag and drop area layouts
* Multiple shortcodes in one form
* Identify files by adding ID to the shortcode. The ID value will be used as a prefix in the filename
* Set the maximum uploaded files limit in global settings, or in the shortcode
* Save or remove all temporary files from the server after sending the form. But if you want to keep the files on the server, please, install [Contact Form 7 Database Addon – CFDB7](https://wordpress.org/plugins/contact-form-cfdb7/) plugin to access the files
* Supports Google Chrome, Mozilla Firefox, Microsoft Edge, Safari. Doesn't support IE 11 and lower

= Limitations =

* Transparent background in PNG files will become solid black
* Animated GIF files will only have 1st frame after compression
* Doesn't support IE

== Frequently Asked Questions ==

= How can I send feedback or get help with a bug? =

For any bug reports go to <a href="https://wordpress.org/support/plugin/images-optimize-and-upload-cf7/">Support</a> page.
Or <a href="https://github.com/yesworld/plugin-wp-optimize-image/issues/">Github issue</a> page.

== Installation ==

To install this plugin see below:

1. Upload the plugin files to the `/wp-content/plugins/images-optimize-and-upload-cf7/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Screenshots ==

1. Generate Shortcode in Contact Form 7 Plugin - Back End
2. Plugin Settings - Back End
3. Drag-n-Drop Field and File Attachment - Front End

== Changelog ==

= 2.1.4 =
Fixed the issue with the required field not sending the files.
Security improvements.
Little fixes.

= 2.1.3 =
Fix work with other upload plugins.

= 2.1.2 =
Added an option to remove or save temp files

= 2.1.1 =
Added an option AutoRotate
Upgraded the plugin https://github.com/davejm/client-compress

= 2.1.0 =
Fixed Contact Form CFDB7 integration
Fixed JS void error

= 2.0.2 =
Added an option to keep the files on the server, need to install Contact Form 7 Database Addon – CFDB7 plugin.
Added an option to set the maximum upload files limit in the shortcode.
Added the support of multiple shortcodes in one form.
Added an option to add a prefix to files from the ID value of the shortcode.

= 2.0.1 =
Added template for editing drag and drop area layout.

= 2.0.0 =
Fix file extension error, lower case and uppercase.
Added file limit settings.
Added template editor for file upload thumbnails.

= 1.1.0 =
Fix styles for mobile.

= 1.0.9 =
Microsoft Edge support.

= 1.0.8 =
Localization fixes.

= 1.0.7 =
Added new features and fixes.
