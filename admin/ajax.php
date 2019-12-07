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
        $this->preg_pattern_img = '/\.'.YR3K_UPLOAD_TYPE_FILES.'$/i';

        // Ajax Upload Images
        add_action('wp_ajax_yr_api_uploader', [$this, 'upload']);
        add_action('wp_ajax_nopriv_yr_api_uploader', [$this, 'upload']);

        add_action('wp_ajax_yr_api_delete', [$this, 'delete']);
        add_action('wp_ajax_nopriv_yr_api_delete', [$this, 'delete']);
    }

    /**
     * Upload files on frontend with ajax.
     */
    public function upload()
    {
        $files = $this->prepareFiles($_FILES[self::KEY_FILES]);

        $uploads_dir = wpcf7_maybe_add_random_dir(YR3K_UPLOAD_TEMP_DIR);
        $randomFolder = basename($uploads_dir);

        $maxFiles = (int) get_option('yr-images-optimize-upload-maxFiles', 3);

        $json = [];
        foreach ($files as $k => $file) {
            if ($maxFiles == $k) {
                $textError = _n('Maximum %d image is allowed.', 'Maximum %d images is allowed.', $maxFiles, YR3K_UPLOAD_REGISTRATION_NAME);
                $textError = sprintf($textError, $maxFiles);
                wp_send_json_error($textError);

                return;
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                wp_send_json_error(wpcf7_get_message('upload_failed'));

                return;
            }

            if (1 != preg_match($this->preg_pattern_img, $file['name'])) {
                wp_send_json_error(YR3K_UPLOAD_ERRORS['incorrect_type']);

                return;
            }

            // Check and create file name
            $filename = wpcf7_canonicalize($file['name'], 'as-is');
            $filename = wpcf7_antiscript_file_name($filename);

            // Add filter on upload file name
            $filename = apply_filters('wpcf7_upload_file_name', $filename, $file['name']);

            // Generate new unique filename
            $filename = wp_unique_filename($uploads_dir, $filename);
            $new_file = path_join($uploads_dir, $filename);

            // Upload File
            if (false === move_uploaded_file($file['tmp_name'], $new_file)) {
                wp_send_json_error(wpcf7_get_message('upload_failed'));

                return;
            }

            $key = $randomFolder.'||'.str_replace('/', '-', $filename);

            $json[] = [
                'key' => $file['key'],
                'value' => Yr3kBaseEncoder::encode($key),
            ];

            chmod($new_file, 0644);
        }

        wp_send_json_success($json);
        die();
    }

    /**
     * Delete uploaded files on frontend with ajax.
     */
    public function delete()
    {
        if (!isset($_POST['key']) || empty($_POST['key'])) {
            wp_send_json_error(wpcf7_get_message('invalid_required'));

            return;
        }

        $key = sanitize_text_field($_POST['key']);
        $pathFile = Yr3kBaseEncoder::decode($key);
        if (2 != count($pathFile)) {
            wp_send_json_error(wpcf7_get_message('invalid_required'));

            return;
        }

        $file_path = path_join(
            YR3K_UPLOAD_TEMP_DIR,
            implode('/', $pathFile)
        );

        if (!file_exists($file_path)) {
            wp_send_json_error(wpcf7_get_message('invalid_required'));

            return;
        }

        wp_delete_file($file_path);
        wp_send_json_success();
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
        $new_array = [];
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < count($file_post['name']); ++$i) {
            foreach ($file_keys as $key) {
                $new_array[$i][$key] = $file_post[$key][$i];
            }
            $new_array[$i]['key'] = isset($_POST[self::KEY_FILES_CLASS_NAME][$i]) ? sanitize_text_field($_POST[self::KEY_FILES_CLASS_NAME][$i]) : '';
        }

        return $new_array;
    }
}
