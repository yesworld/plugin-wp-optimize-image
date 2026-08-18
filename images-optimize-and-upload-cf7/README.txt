=== Images Optimize and Upload CF7 ===
Contributors: yesworld, bruklig
Tags: images, compression, contact form 7, drag and drop, multiple file
Requires at least: 5.2.2
Tested up to: 7.0
Stable tag: 2.4.0
Requires PHP: 7.1
License: GNU GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows uploading and client-side compression of multiple images in Contact Form 7.

== Description ==

Images Optimize and Upload CF7 is an extension for [Contact Form 7](https://wordpress.org/plugins/contact-form-7/). It lets visitors select multiple images with drag and drop or a Browse button, compresses browser-supported images before submission, and attaches the accepted files to the Contact Form 7 email.

Files are sent only as part of the final native Contact Form 7 multipart submission. The plugin does not upload files through a separate public AJAX endpoint or keep a public plugin upload directory.

Tested with Contact Form 7 version 6.1.6.

= Features =

* Drag and drop or Browse selection of multiple images.
* Client-side compression, resizing and EXIF rotation correction for browser-supported images.
* Resizes images to a configurable maximum width and height while preserving aspect ratio.
* Sends files with the final Contact Form 7 multipart request, then attaches accepted files to the email automatically.
* Uses Contact Form 7's temporary upload directory; files are not stored as public plugin uploads.
* Client-side and server-side validation of file extensions; non-HEIC files must also be real images.
* A configurable global maximum file count, with an optional per-field `max-file` override.
* Multiple upload fields in one form. An `id` option adds an `ID_..._` prefix to the resulting filename.
* Configurable allowed extensions, file-name lowercasing, compression settings, preview layout and drag-and-drop layout.
* Experimental HEIC/HEIF processing through Imagick when the server supports the relevant format and the administrator enables it.
* HEIC/HEIF files use a vertical format label instead of an image preview.
* Responsive layout for current Google Chrome, Mozilla Firefox, Microsoft Edge and Safari versions. Internet Explorer is not supported.

= Upload flow and mail attachments =

The visible picker is backed by a real Contact Form 7 file input. Before the form is submitted, the plugin replaces browser-supported images in that input with their compressed versions; it does not send them to the server yet. Contact Form 7 then moves the multipart upload to its own temporary directory, and the plugin attaches only its validated files to the form's email.

No manual entry is required in Contact Form 7's `File attachments` field for this plugin's fields. Add `[your-field-name]` to the mail body if the email should also list the accepted attachment names.

= HEIC/HEIF =

The plugin checks the formats reported by Imagick on the plugin settings page. If the server supports HEIC and/or HEIF, an administrator can enable the experimental processing option.

When enabled, a HEIC/HEIF file is sent without client-side compression, converted to JPEG on the server, and then attached to the email. The server conversion applies the configured resize, quality, minimum quality, quality-step and target-size settings. The frontend deliberately shows the format label (`HEIC` or `HEIF`) rather than an image thumbnail.

When server processing is disabled or the required Imagick format is unavailable, HEIC/HEIF files are skipped. The server also skips them when they are submitted directly or from an outdated cached page, so they cannot bypass this setting or be attached to email.

= Using the form tag =

Add a field in the Contact Form 7 form editor, for example:

`[upload_image images max-file:3]`

Use `upload_image*` to make the field required. If `max-file` is omitted, the global **Maximum Files** setting is used. The `id:example` option prefixes resulting file names with `ID_example_`.

Configure allowed extensions and compression settings under **Images Optimize & Upload - Settings**. Adding `heic` or `heif` to the extension setting alone does not enable those formats; server support and the HEIC/HEIF processing option are both required.

= Limitations =

* Transparent background in PNG files will become solid black
* Animated GIF files will only have 1st frame after compression
* Browser-side compression requires the File, DataTransfer and Canvas APIs. If they are unavailable, the form displays an error instead of sending uncompressed files.
* The individual file-size limit is controlled by WordPress, PHP and Contact Form 7.
* HEIC/HEIF conversion depends on the server's Imagick/ImageMagick build and is experimental.
* Doesn't support Internet Explorer

== Frequently Asked Questions ==

= How can I send feedback or get help with a bug? =

For any bug reports go to <a href="https://wordpress.org/support/plugin/images-optimize-and-upload-cf7/">Support</a> page.
Or <a href="https://github.com/yesworld/plugin-wp-optimize-image/issues/">Github issue</a> page.

== Installation ==

Contact Form 7 must be installed and active.

1. Upload the plugin files to the `/wp-content/plugins/images-optimize-and-upload-cf7/` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Open **Images Optimize & Upload - Settings** and configure the allowed extensions, maximum file count and compression settings.
4. Add an `upload_image` or required `upload_image*` tag to a Contact Form 7 form.

== Screenshots ==

1. Generate Shortcode in Contact Form 7 Plugin - Back End
2. Plugin Settings - Back End
3. Drag-n-Drop Field and File Attachment - Front End

== Changelog ==

= 2.4.0 =
Reworked uploads to use the native Contact Form 7 multipart submission flow. Client-side compression is retained, while files are no longer uploaded through a separate public AJAX endpoint or stored in a public plugin upload directory.
Removed the obsolete plugin temporary-file setting; Contact Form 7 now manages temporary upload files.
HEIC/HEIF files are converted to JPEG only when explicitly enabled and supported by Imagick. Unsupported HEIC/HEIF files are skipped and never attached to email. HEIC/HEIF files now display a format label instead of an image thumbnail.
Improved the maximum-file-count behaviour so a rejected extra selection does not discard files already selected for the form.

= 2.3.1 =
Added an experimental HEIC/HEIF server-side processing option for servers that support these formats through Imagick.

= 2.3.0 =
Added a plugin setting to edit allowed upload file extensions.
Added an option to convert uploaded file names to lowercase before saving.

= 2.2.3 =
Fixed default value

= 2.2.2 =
Fixed unauthenticated arbitrary file upload vulnerability.
Added nonce and upload session token validation for AJAX uploads.
Added server-side image file validation and improved upload error handling.

= 2.2.1 =
Fixed file deletion vulnerability
Cleanup unused temp files after submission
Added compatibility with other file upload plugins

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
