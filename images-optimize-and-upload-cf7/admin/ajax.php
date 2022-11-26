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

        add_action('wp_ajax_yr_api_delete', [$this, 'delete']);
        add_action('wp_ajax_nopriv_yr_api_delete', [$this, 'delete']);
    }

    /**
     * Upload files on frontend with ajax.
     */
    public function upload()
    {
        $files = $this->prepareFiles($_FILES[self::KEY_FILES]);
        if ($files === null) {
            return;
        }

        $formId = $_POST['id'];

        $uploads_dir = wpcf7_maybe_add_random_dir(YR3K_UPLOAD_TEMP_DIR);
        $randomFolder = basename($uploads_dir);

        $json = [];
        foreach ($files as $k => $file) {

            if (!is_uploaded_file($file['tmp_name'])) {
                wp_send_json_error(wpcf7_get_message('upload_failed'));

                return;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!in_array($extension, $this->preg_pattern_img)) {
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
     * Delete uploaded files on frontend with ajax.
     */
    public function delete()
    {
        if (
            (!isset($_POST['file']) || empty($_POST['file']))
            || (count(explode('/', $_POST['file'])) !== 2)
        ) {
            wp_send_json_error(wpcf7_get_message('invalid_required'));

            return;
        }

        $pathFile = sanitize_text_field($_POST['file']);
        $file_path = path_join(YR3K_UPLOAD_TEMP_DIR, $pathFile);

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
        if ($file_post === null) {
            return null;
        }

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
