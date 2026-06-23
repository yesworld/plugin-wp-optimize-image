<?php

class Yr3kUploaderApi
{
    const KEY_FILES = 'upload-image';
    const KEY_FILES_CLASS_NAME = 'upload-image-key';

    /**
     * Initialize hooks
     * Yr3kUploaderApi constructor.
     */
    public function __construct()
    {
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
            $serverProcessedHeic = $this->shouldProcessHeicOnServer($file);

            // Check and create file name
            $originalName = sanitize_text_field(wp_unslash($file['name']));
            $filename = wpcf7_canonicalize($originalName, 'as-is');
            $filename = sanitize_file_name($filename);
            $filename = wpcf7_antiscript_file_name($filename);
            if ($serverProcessedHeic) {
                $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
            }

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
            $heicInfo = null;
            if ($serverProcessedHeic) {
                $heicInfo = $this->processHeicFile($file['tmp_name'], $new_file);
                $uploaded = false !== $heicInfo;
            } else {
                $uploaded = move_uploaded_file($file['tmp_name'], $new_file);
            }

            if (false === $uploaded) {
                wp_send_json_error(wpcf7_get_message('upload_failed'));

                return;
            }

            $item = [
                'key' => $file['key'],
                'temp' => $randomFolder,
                'value' => str_replace('/', '-', $filename),
            ];

            if ($serverProcessedHeic) {
                $item['info'] = $heicInfo;
                $item['preview_url'] = $this->getTempFileUrl($randomFolder, $filename);
                $item['name'] = $originalName;
            }

            $json[] = $item;

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

        if ('' === $extension || !in_array($extension, Yr3kUploaderSettings::getAllowedExtensions(), true)) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['incorrect_type'], 400);
        }

        $mime = $this->getImageMime($file['tmp_name']);
        if (!$mime || 0 !== strpos($mime, 'image/')) {
            wp_send_json_error(YR3K_UPLOAD_ERRORS['incorrect_type'], 400);
        }
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

    private function shouldProcessHeicOnServer($file)
    {
        if (!Yr3kUploaderSettings::isHeicServerProcessingEnabled()) {
            return false;
        }

        $originalName = isset($file['name']) ? sanitize_text_field(wp_unslash($file['name'])) : '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return in_array($extension, ['heic', 'heif'], true);
    }

    private function processHeicFile($source, $destination)
    {
        try {
            $image = new Imagick();
            $image->readImage($source);
            $image->setIteratorIndex(0);

            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            } elseif (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }

            $info = [
                'startSizeMB' => filesize($source) * 0.000001,
                'startWidth' => (int) $image->getImageWidth(),
                'startHeight' => (int) $image->getImageHeight(),
            ];

            $this->resizeImage($image);
            $image->setImageFormat('jpeg');
            $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            $image->stripImage();

            $written = $this->writeCompressedJpeg($image, $destination);
            if (!$written) {
                $image->clear();
                return false;
            }

            $info['endSizeMB'] = filesize($destination) * 0.000001;
            $info['endWidth'] = (int) $image->getImageWidth();
            $info['endHeight'] = (int) $image->getImageHeight();

            $image->clear();

            return $info;
        } catch (Exception $exception) {
            return false;
        }
    }

    private function resizeImage(Imagick $image)
    {
        if (1 !== (int) get_option('yr-images-optimize-upload-resize', 1)) {
            return;
        }

        $width = (int) $image->getImageWidth();
        $height = (int) $image->getImageHeight();
        $maxWidth = (int) Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-maxWidth', 1920);
        $maxHeight = (int) Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-maxHeight', 1920);

        if ($width <= 0 || $height <= 0 || $maxWidth <= 0 || $maxHeight <= 0 || ($width <= $maxWidth && $height <= $maxHeight)) {
            return;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1);
    }

    private function writeCompressedJpeg(Imagick $image, $destination)
    {
        $quality = (int) round(Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-quality', 0.75) * 100);
        $minQuality = (int) round(Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-minQuality', 0.5) * 100);
        $step = (int) round(Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-qualityStepSize', 0.1) * 100);
        $targetSize = Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-targetSize', 0.25) * 1000000;

        $quality = min(100, max(1, $quality));
        $minQuality = min($quality, max(1, $minQuality));
        $step = max(1, $step);

        do {
            $image->setImageCompressionQuality($quality);
            $blob = $image->getImagesBlob();

            if (false === file_put_contents($destination, $blob)) {
                return false;
            }

            if ($targetSize <= 0 || filesize($destination) <= $targetSize || $quality <= $minQuality) {
                return true;
            }

            $quality = max($minQuality, $quality - $step);
        } while (true);
    }

    private function getTempFileUrl($folder, $filename)
    {
        return trailingslashit(YR3K_UPLOAD_BASEURL) . rawurlencode($folder) . '/' . rawurlencode($filename);
    }
}
