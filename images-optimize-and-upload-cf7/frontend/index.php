<?php

class Yr3kUploaderFrontend
{
    const NAME_HANDLE = 'yr3k-optimizer-3000';

    /**
     * Files that passed plugin validation during the current CF7 submission.
     * Only these paths may become mail attachments.
     *
     * @var array
     */
    private $acceptedFiles = [];

    public function __construct()
    {
        add_action('wpcf7_init', [$this, 'generate_tag_to_html']);
        add_action('wpcf7_swv_create_schema', [$this, 'addSchemaRules'], 10, 2);

        // Keep the retired endpoint fail-closed for cached pages and direct
        // requests made by earlier plugin versions.
        add_action('wp_ajax_yr_api_uploader', [$this, 'rejectLegacyAjaxUpload']);
        add_action('wp_ajax_nopriv_yr_api_uploader', [$this, 'rejectLegacyAjaxUpload']);
        add_action('wp_ajax_yr_api_uploader_session', [$this, 'rejectLegacyAjaxUpload']);
        add_action('wp_ajax_nopriv_yr_api_uploader_session', [$this, 'rejectLegacyAjaxUpload']);

        // CF7 moves real multipart uploads to its protected temporary folder.
        add_filter('wpcf7_upload_file_name', [$this, 'filterUploadFileName'], 20, 3);
        add_filter('wpcf7_validate_upload_image', [$this, 'validation'], 10, 3);
        add_filter('wpcf7_validate_upload_image*', [$this, 'validation'], 10, 3);

        add_action('wpcf7_before_send_mail', [$this, 'beforeSendMail'], 999, 3);
        add_filter('wpcf7_mail_tag_replaced_upload_image', [$this, 'replaceMailTag'], 10, 4);
        add_filter('wpcf7_mail_tag_replaced_upload_image*', [$this, 'replaceMailTag'], 10, 4);
    }

    public function rejectLegacyAjaxUpload()
    {
        wp_send_json_error(YR3K_UPLOAD_ERRORS['failed_upload'], 403);
    }

    /**
     * Register validation rules used by CF7 before it moves uploads to disk.
     * HEIC/HEIF are accepted here even when disabled so that a stale page or a
     * manual POST can be skipped safely later instead of becoming an attachment.
     */
    public function addSchemaRules($schema, $contactForm)
    {
        $tags = $contactForm->scan_form_tags([
            'basetype' => [YR3K_UPLOAD_SHORTCODE],
        ]);

        foreach ($tags as $tag) {
            if ($tag->is_required()) {
                $schema->add_rule(wpcf7_swv_create_rule('requiredfile', [
                    'field' => $tag->name,
                    'error' => wpcf7_get_message('invalid_required'),
                ]));
            }

            $extensions = array_unique(array_merge(
                Yr3kUploaderSettings::getAllowedExtensions(),
                ['heic', 'heif']
            ));

            $schema->add_rule(wpcf7_swv_create_rule('file', [
                'field' => $tag->name,
                'accept' => array_map(function ($extension) {
                    return '.' . $extension;
                }, $extensions),
                'error' => YR3K_UPLOAD_ERRORS['incorrect_type'],
            ]));

            $schema->add_rule(wpcf7_swv_create_rule('maxfilesize', [
                'field' => $tag->name,
                'threshold' => wp_max_upload_size(),
                'error' => wpcf7_get_message('upload_file_too_large'),
            ]));
        }
    }

    /**
     * Validate actual files after CF7 has placed them in its protected temporary
     * directory. Unsupported HEIC/HEIF files are deliberately skipped.
     */
    public function validation($result, $tag, $args = [])
    {
        $uploadedFiles = isset($args['uploaded_files']) && is_array($args['uploaded_files'])
            ? array_values($args['uploaded_files'])
            : [];
        $sourceNames = $this->getSourceNames($tag->name);
        $maxFiles = $this->parseTagMaxFile($tag)['max-file'];

        $this->acceptedFiles[$tag->name] = [];

        foreach ($uploadedFiles as $index => $path) {
            $sourceName = isset($sourceNames[$index]) ? $sourceNames[$index] : wp_basename($path);
            $extension = $this->getExtension($sourceName);

            if ($this->isHeicExtension($extension)) {
                // A manually submitted or stale HEIC file must never be attached
                // when this server cannot safely convert it.
                if (!Yr3kUploaderSettings::canProcessHeicExtension($extension)) {
                    continue;
                }

                if (false === $this->processHeicFile($path)) {
                    $result->invalidate($tag, YR3K_UPLOAD_ERRORS['failed_upload']);
                    return $result;
                }

                $this->acceptedFiles[$tag->name][] = $path;
                continue;
            }

            if (!$this->isAllowedNonHeicExtension($extension) || !$this->isImageFile($path)) {
                $result->invalidate($tag, YR3K_UPLOAD_ERRORS['incorrect_type']);
                return $result;
            }

            $this->acceptedFiles[$tag->name][] = $path;
        }

        if (count($this->acceptedFiles[$tag->name]) > $maxFiles) {
            $result->invalidate($tag, sprintf(
                _n('Maximum %d image is allowed.', 'Maximum %d images is allowed.', $maxFiles, YR3K_UPLOAD_REGISTRATION_NAME),
                $maxFiles
            ));

            return $result;
        }

        if ($tag->is_required() && empty($this->acceptedFiles[$tag->name])) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
        }

        return $result;
    }

    /**
     * Convert enabled HEIC uploads to a .jpg file name before CF7 records the
     * temporary path in its submission object.
     */
    public function filterUploadFileName($filename, $originalName, $options)
    {
        if (!$this->isUploaderTagOptions($options)) {
            return $filename;
        }

        if (1 === (int) get_option('yr-images-optimize-upload-lowercase-filenames', 0)) {
            $filename = strtolower($filename);
        }

        $extension = $this->getExtension($originalName);
        if (Yr3kUploaderSettings::canProcessHeicExtension($extension)) {
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        }

        $id = method_exists($options['tag'], 'get_id_option') ? $options['tag']->get_id_option() : '';
        return $id ? 'ID_' . sanitize_file_name($id) . '_' . $filename : $filename;
    }

    /**
     * Add only validated plugin uploads to the mail attachment list.
     */
    public function beforeSendMail($wpcf7)
    {
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return $wpcf7;
        }

        $files = [];
        foreach ($wpcf7->scan_form_tags() as $tag) {
            if (YR3K_UPLOAD_SHORTCODE !== $tag->basetype) {
                continue;
            }

            foreach ((array) ($this->acceptedFiles[$tag->name] ?? []) as $file) {
                if ($this->isCf7TemporaryFile($file)) {
                    $files[] = $file;
                }
            }
        }

        if (empty($files)) {
            return $wpcf7;
        }

        $mail = $wpcf7->prop('mail');
        $existing = isset($mail['attachments']) && '' !== trim($mail['attachments'])
            ? preg_split('/\r\n|\r|\n/', $mail['attachments'])
            : [];
        $mail['attachments'] = implode(PHP_EOL, array_unique(array_filter(array_merge($existing, $files))));
        $wpcf7->set_properties(['mail' => $mail]);

        return $wpcf7;
    }

    /**
     * Render a safe list of attached file names in mail templates.
     */
    public function replaceMailTag($replaced, $submitted, $html, $mailTag)
    {
        $name = method_exists($mailTag, 'field_name') ? $mailTag->field_name() : '';
        if ('' === $name || empty($this->acceptedFiles[$name])) {
            return $replaced;
        }

        return wpcf7_flat_join(array_map('wp_basename', $this->acceptedFiles[$name]));
    }

    /**
     * Register upload_image as a real CF7 file-uploading tag.
     */
    public function generate_tag_to_html()
    {
        wpcf7_add_form_tag(
            ['upload_image', 'upload_image*'],
            [$this, 'replaceTagHandler'],
            [
                'name-attr' => true,
                'file-uploading' => true,
            ]
        );
    }

    /**
     * Convert the plugin form tag into a real multipart file field.
     */
    public function replaceTagHandler($tag)
    {
        if (empty($tag->name)) {
            return '';
        }

        $validationError = wpcf7_get_validation_error($tag->name);
        $class = wpcf7_form_controls_class($tag->type);
        if ($validationError) {
            $class .= ' wpcf7-not-valid';
        }

        $dataTag = $this->parseTagMaxFile($tag);
        $atts = [
            'size' => $tag->get_size_option('40'),
            'class' => $tag->get_class_option($class),
            'id' => $tag->get_id_option(),
            'tabindex' => $tag->get_option('tabindex', 'signed_int', true),
            'type' => 'file',
            'name' => $tag->name . '[]',
            'data-name' => $tag->name,
            'max-file-error' => $dataTag['max-file-error'],
            'max-file' => $dataTag['max-file'],
            'accept' => $this->getAcceptFormats(),
            'aria-invalid' => $validationError ? 'true' : 'false',
        ];

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $this->loadEnqueueScript();

        return $this->template(
            sanitize_html_class($tag->name),
            wpcf7_format_atts($atts),
            $validationError
        );
    }

    /**
     * Load client-side compression and preview behaviour.
     */
    public function loadEnqueueScript()
    {
        wp_enqueue_script(
            self::NAME_HANDLE . '-libs',
            plugins_url('frontend/assets/libs.js', __DIR__),
            null,
            YR3K_UPLOAD_VERSION,
            true
        );

        wp_enqueue_script(
            self::NAME_HANDLE,
            plugins_url('frontend/assets/script.js', __DIR__),
            ['jquery', self::NAME_HANDLE . '-libs'],
            YR3K_UPLOAD_VERSION,
            true
        );

        wp_enqueue_script(
            'init',
            plugins_url('frontend/assets/init.js', __DIR__),
            ['jquery', self::NAME_HANDLE . '-libs', self::NAME_HANDLE],
            YR3K_UPLOAD_VERSION,
            true
        );

        wp_localize_script(
            self::NAME_HANDLE,
            'YR3K_UPLOADER_OPTIONS',
            [
                'targetSize' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-targetSize', 0.25),
                'quality' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-quality', 0.75),
                'minQuality' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-minQuality', 0.5),
                'qualityStepSize' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-qualityStepSize', 0.1),
                'maxWidth' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-maxWidth', 1920),
                'maxHeight' => Yr3kUploaderSettings::getNumberOption('yr-images-optimize-upload-maxHeight', 1920),
                'resize' => get_option('yr-images-optimize-upload-resize', 1),
                'throwIfSizeNotReached' => get_option('yr-images-optimize-upload-throwIfSizeNotReached', 0),
                'formatFile' => implode('|', Yr3kUploaderSettings::getAllowedExtensions()),
                'heicServerProcessing' => Yr3kUploaderSettings::isHeicServerProcessingEnabled() ? 1 : 0,
                'templatePreview' => get_option('yr-images-optimize-upload-template', Yr3kUploaderSettings::getTemplatePreview()),
                'templateDndArea' => get_option('yr-images-optimize-upload-template-dnd', Yr3kUploaderSettings::getTemplateDndArea()),
                'language' => [
                    'info_file_origin' => __('Original size', YR3K_UPLOAD_REGISTRATION_NAME),
                    'info_file_compress' => __('Compressed', YR3K_UPLOAD_REGISTRATION_NAME),
                    'info_file_delete' => __('Delete', YR3K_UPLOAD_REGISTRATION_NAME),
                    'wrong_format' => __('Wrong file format', YR3K_UPLOAD_REGISTRATION_NAME),
                    'heic_skipped' => __('HEIC/HEIF was not added because server-side processing is unavailable.', YR3K_UPLOAD_REGISTRATION_NAME),
                    'browser_unsupported' => __('This browser cannot prepare compressed files for form submission.', YR3K_UPLOAD_REGISTRATION_NAME),
                    'preparing' => __('Preparing images…', YR3K_UPLOAD_REGISTRATION_NAME),
                ],
            ]
        );

        wp_enqueue_style(
            self::NAME_HANDLE,
            plugins_url('frontend/assets/style.css', __DIR__),
            '',
            YR3K_UPLOAD_VERSION
        );
    }

    private function parseTagMaxFile($tag)
    {
        $maxFiles = $tag->get_option('max-file', '', true);
        if (!$maxFiles) {
            $maxFiles = get_option('yr-images-optimize-upload-maxFiles', 3);
        }
        $maxFiles = max(1, absint($maxFiles));
        $textError = _n('Maximum %d image is allowed.', 'Maximum %d images is allowed.', $maxFiles, YR3K_UPLOAD_REGISTRATION_NAME);

        return [
            'max-file' => $maxFiles,
            'max-file-error' => sprintf($textError, $maxFiles),
        ];
    }

    private function getAcceptFormats()
    {
        return implode(',', array_map(function ($extension) {
            return '.' . $extension;
        }, Yr3kUploaderSettings::getAllowedExtensions()));
    }

    private function getSourceNames($fieldName)
    {
        if (!isset($_FILES[$fieldName]['name'])) {
            return [];
        }

        return array_values(wpcf7_array_flatten(wp_unslash($_FILES[$fieldName]['name'])));
    }

    private function getExtension($filename)
    {
        return strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    }

    private function isHeicExtension($extension)
    {
        return in_array($extension, ['heic', 'heif'], true);
    }

    private function isAllowedNonHeicExtension($extension)
    {
        return !$this->isHeicExtension($extension)
            && in_array($extension, Yr3kUploaderSettings::getAllowedExtensions(), true);
    }

    private function isImageFile($path)
    {
        $mime = $this->getImageMime($path);
        return $mime && 0 === strpos($mime, 'image/');
    }

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

    private function isUploaderTagOptions($options)
    {
        return is_array($options)
            && isset($options['tag'])
            && isset($options['tag']->basetype)
            && YR3K_UPLOAD_SHORTCODE === $options['tag']->basetype;
    }

    private function isCf7TemporaryFile($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return false;
        }

        $base = realpath(wpcf7_upload_tmp_dir());
        $path = realpath($file);
        if (!$base || !$path) {
            return false;
        }

        $base = rtrim(wp_normalize_path($base), '/') . '/';
        return 0 === strpos(wp_normalize_path($path), $base);
    }

    /**
     * Convert an HEIC/HEIF temporary upload to JPEG in place. The file name was
     * already changed to .jpg by filterUploadFileName().
     */
    private function processHeicFile($source)
    {
        if (!class_exists('Imagick') || !$this->isCf7TemporaryFile($source)) {
            return false;
        }

        $temporary = path_join(dirname($source), '.' . wp_basename($source) . '.yr3k-converting');

        try {
            $image = new Imagick();
            $image->readImage($source);
            $image->setIteratorIndex(0);

            if (method_exists($image, 'autoOrient')) {
                $image->autoOrient();
            } elseif (method_exists($image, 'autoOrientImage')) {
                $image->autoOrientImage();
            }

            $this->resizeImage($image);
            $image->setImageFormat('jpeg');
            $image->setImageCompression(Imagick::COMPRESSION_JPEG);
            $image->stripImage();

            if (!$this->writeCompressedJpeg($image, $temporary) || 'image/jpeg' !== $this->getImageMime($temporary)) {
                $image->clear();
                @unlink($temporary);
                return false;
            }

            $image->clear();
            if (!@rename($temporary, $source)) {
                @unlink($temporary);
                return false;
            }

            @chmod($source, 0400);
            return true;
        } catch (Exception $exception) {
            @unlink($temporary);
            return false;
        }
    }

    private function resizeImage($image)
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
        $image->resizeImage(
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
            Imagick::FILTER_LANCZOS,
            1
        );
    }

    private function writeCompressedJpeg($image, $destination)
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
            if (false === file_put_contents($destination, $image->getImagesBlob())) {
                return false;
            }

            if ($targetSize <= 0 || filesize($destination) <= $targetSize || $quality <= $minQuality) {
                return true;
            }

            $quality = max($minQuality, $quality - $step);
        } while (true);
    }

    private function template($name, $attrs, $error)
    {
        return sprintf(
            '<span class="wpcf7-form-control-wrap %1$s wpcf7-images-optimize-upload-wrap"><input %2$s multiple="multiple"/>%3$s</span>',
            $name,
            $attrs,
            $error
        );
    }
}
