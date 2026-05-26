<?php

class Yr3kUploaderApi
{
    const KEY_FILES = 'upload-image';
    const KEY_FILES_CLASS_NAME = 'upload-image-key';

    private $preg_pattern_img;

    /**
     * Initialize hooks
     * Yr3kUploaderApi constructor.
     */
    public function __construct()
    {
        $this->preg_pattern_img = explode('|', YR3K_UPLOAD_FILE_FORMATS);

        // Ajax Upload Images
        add_action('wp_ajax_yr_api_uploader', [$this, 'upload']);
        add_action('wp_ajax_nopriv_yr_api_uploader', [$this, 'upload']);
    }

    /**
     * Upload files on frontend with ajax.
     */
    public function upload()
    {
        $uploadSession = $this->validateUploadRequest();

        if (!isset($_FILES[self::KEY_FILES])) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['failed_upload'], 400);
        }

        $files = $this->prepareFiles($_FILES[self::KEY_FILES]);
        if ($files === null || 0 === count($files)) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['failed_upload'], 400);
        }

        if (count($files) > (int) $uploadSession['max_files']) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['failed_upload'], 400);
        }

        $formId = isset($_POST['id']) ? sanitize_file_name(sanitize_text_field(wp_unslash($_POST['id']))) : '0';

        $uploads_dir = wpcf7_maybe_add_random_dir(YR3K_UPLOAD_TEMP_DIR);
        $randomFolder = basename($uploads_dir);

        $json = [];
        foreach ($files as $k => $file) {

            $this->validateUploadedFile($file);

            // Check and create file name
            $originalName = sanitize_text_field(wp_unslash($file['name']));
            $filename = wpcf7_canonicalize($originalName, 'as-is');
            $filename = sanitize_file_name($filename);
            $filename = wpcf7_antiscript_file_name($filename);

            // Add filter on upload file name
            $filename = apply_filters('wpcf7_upload_file_name', $filename, $originalName);

            if (1 === (int) get_option('yr-images-optimize-upload-lowercase-filenames', 0)) {
                $filename = strtolower($filename);
            }

            // Generate new unique filename
            $filename = wp_unique_filename($uploads_dir, $filename);
            $filename = $formId == '0' ? $filename : 'ID_' . $formId . '_' . $filename;
            $new_file = path_join($uploads_dir, $filename);

            // Upload File
            if (false === move_uploaded_file($file['tmp_name'], $new_file)) {
                wp_send_json_error(wpcf7_get_message('upload_failed'));

                return;
            }

            $json[] = [
                'key' => $file['key'],
                'temp' => $randomFolder,
                'value' => str_replace('/', '-', $filename),
            ];

            chmod($new_file, 0644);
        }

        wp_send_json_success($json);
        die();
    }

    /**
     * Prepare files for upload.
     *
     * @param $file_post
     *
     * @return array
     */
    public function prepareFiles($file_post)
    {
        if (!is_array($file_post) || !isset($file_post['name'])) {
            return null;
        }

        if (!is_array($file_post['name'])) {
            foreach (array_keys($file_post) as $key) {
                $file_post[$key] = [$file_post[$key]];
            }
        }

        $new_array = [];
        $file_keys = array_keys($file_post);
        $posted_keys = isset($_POST[self::KEY_FILES_CLASS_NAME]) ? wp_unslash($_POST[self::KEY_FILES_CLASS_NAME]) : [];

        for ($i = 0; $i < count($file_post['name']); ++$i) {
            foreach ($file_keys as $key) {
                $new_array[$i][$key] = $file_post[$key][$i];
            }
            $new_array[$i]['key'] = is_array($posted_keys) && isset($posted_keys[$i]) ? sanitize_text_field($posted_keys[$i]) : '';
        }

        return $new_array;
    }

    /**
     * Validate ajax nonce and upload token.
     * The endpoint is public, so every upload must come from a rendered form field.
     *
     * @return array
     */
    private function validateUploadRequest()
    {
        if (!check_ajax_referer(YR3K_UPLOAD_AJAX_NONCE, 'nonce', false)) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['forbidden'], 403);
        }

        // The upload token links this AJAX request to one rendered CF7 field.
        $token = isset($_POST['upload_token']) ? sanitize_text_field(wp_unslash($_POST['upload_token'])) : '';
        if ('' === $token) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['forbidden'], 403);
        }

        $uploadSession = get_transient(YR3K_UPLOAD_TOKEN_PREFIX . md5($token));
        if (!is_array($uploadSession)) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['forbidden'], 403);
        }

        $fieldName = isset($uploadSession['field_name']) ? sanitize_text_field($uploadSession['field_name']) : '';
        if ('' === $fieldName) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['forbidden'], 403);
        }

        return $uploadSession;
    }

    /**
     * Validate that the uploaded file is an allowed image.
     * Client-side checks are not trusted because attackers can POST directly.
     *
     * @param array $file
     */
    private function validateUploadedFile($file)
    {
        if (!isset($file['error']) || UPLOAD_ERR_OK !== (int) $file['error']) {
            wp_send_json_error(wpcf7_get_message('upload_failed'), 400);
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            wp_send_json_error(wpcf7_get_message('upload_failed'), 400);
        }

        if (!empty($file['size']) && $file['size'] > wp_max_upload_size()) {
            wp_send_json_error(wpcf7_get_message('upload_failed'), 400);
        }

        $originalName = isset($file['name']) ? sanitize_text_field(wp_unslash($file['name'])) : '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ('' === $extension || !in_array($extension, $this->getAllowedExtensions(), true)) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['incorrect_type'], 400);
        }

        $mime = $this->getImageMime($file['tmp_name']);
        if (!$mime || 0 !== strpos($mime, 'image/')) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['incorrect_type'], 400);
        }
    }

    /**
     * Return the normalized extension allowlist from plugin settings.
     *
     * @return array
     */
    private function getAllowedExtensions()
    {
        return array_filter(array_map('strtolower', array_map('trim', $this->preg_pattern_img)));
    }

    /**
     * Detect the real image MIME type from file contents.
     *
     * @param string $file
     *
     * @return string|false
     */
    private function getImageMime($file)
    {
        if (function_exists('wp_get_image_mime')) {
            return wp_get_image_mime($file);
        }

        if (function_exists('getimagesize')) {
            $imagesize = @getimagesize($file);
            return isset($imagesize['mime']) ? $imagesize['mime'] : false;
        }

        return false;
    }
}
