<?php

class Yr3kUploaderFrontend
{
    const NAME_HANDLE = 'yr3k-optimizer-3000';
    const KEY_FILES = 'upload-image';

    public function __construct()
    {
        add_action('wpcf7_init', [$this, 'generate_tag_to_html']);

        // Hook before/after for mail cf7
        add_action('wpcf7_before_send_mail', [$this, 'before_send_mail'], 9, 1);
        add_action('wpcf7_mail_sent', [$this, 'after_mail_sent'], 100);

        // Validation
        add_filter('wpcf7_validate_upload_image', [$this, 'validation'], 10, 2);
        add_filter('wpcf7_validate_upload_image*', [$this, 'validation'], 10, 2);
    }

    /**
     * Check required and limit the files.
     *
     * @param $result
     * @param $tag
     *
     * @return mixed
     */
    public function validation($result, $tag)
    {
        $files = (isset($_POST[self::KEY_FILES]) ? $_POST[self::KEY_FILES] : []);

        // Check if we have files or if it's empty
        if (!is_array($files) || (0 == count($files) && $tag->is_required())) {
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));

            return $result;
        }

        $maxFiles = (int) get_option('yr-images-optimize-upload-maxFiles', 3);
        if (count($files) > $maxFiles) {
            $textError = _n('Maximum %d image is allowed.', 'Maximum %d images are allowed.', $maxFiles, YR3K_UPLOAD_REGISTRATION_NAME);
            $textError = sprintf($textError, $maxFiles);
            $result->invalidate($tag, $textError);

            return $result;
        }

        foreach ($files as $raw) {
            $str = sanitize_text_field($raw);
            $pathFile = Yr3kBaseEncoder::decode($str);

            if (2 == count($pathFile)) {
                continue;
            }

            $result->invalidate($tag, wpcf7_get_message('invalid_required'));

            return $result;
        }

        return $result;
    }

    /**
     * After form sent remove temporary files.
     *
     * @param WPCF7_ContactForm $wpcf7
     *
     * @return WPCF7_ContactForm
     */
    public function after_mail_sent(WPCF7_ContactForm $wpcf7)
    {
        $bool = $this->isOptimizeFiled($wpcf7->scan_form_tags());
        $wpcf7->message('mail_sent_ok');

        if (!$bool) {
            return $wpcf7;
        }

        $mail = $wpcf7->prop('mail');
        foreach (explode("\n", $mail['attachments']) as $file) {
            if (!file_exists($file)) {
                continue;
            }

            unlink($file);
        }

        return $wpcf7;
    }

    /**
     * Attach files to the form.
     *
     * @param WPCF7_ContactForm $wpcf7
     *
     * @return WPCF7_ContactForm
     */
    public function before_send_mail(WPCF7_ContactForm $wpcf7)
    {
        $submission = WPCF7_Submission::get_instance();

        // Get posted data
        $posts = $submission->get_posted_data();

        if (!isset($posts[self::KEY_FILES]) || 0 == count($posts[self::KEY_FILES])) {
            return $wpcf7;
        }

        $files = [];
        foreach ($posts[self::KEY_FILES] as $key => $raw) {
            $pathFile = Yr3kBaseEncoder::decode($raw);

            $file = implode('/', $pathFile);

            $fullPath = path_join(YR3K_UPLOAD_TEMP_DIR, $file);
            $files[] = $fullPath;
            $submission->add_uploaded_file($pathFile[1], $fullPath);
        }

        // Prop email
        $mail = $wpcf7->prop('mail');
        $mail['attachments'] = implode("\n", $files);
        $wpcf7->set_properties(['mail' => $mail]);

        return $wpcf7;
    }

    /**
     * Generate tag upload_image.
     */
    public function generate_tag_to_html()
    {
        wpcf7_add_form_tag(
            ['upload_image', 'upload_image*'],
            [$this, 'replace_tag_handler'],
            ['name-attr' => true]
        );
    }

    /**
     * Convert shortcode to html.
     *
     * @param WPCF7_FormTag $tag
     *
     * @return string
     */
    public function replace_tag_handler(WPCF7_FormTag $tag)
    {
        if (empty($tag->name)) {
            return '';
        }

        $this->load_enqueue_script();

        $validation_error = wpcf7_get_validation_error($tag->name);
        $class = wpcf7_form_controls_class($tag->type);

        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }

        $atts = [];
        $atts['size'] = $tag->get_size_option('40');
        $atts['class'] = $tag->get_class_option($class);
        $atts['id'] = $tag->get_id_option();
        $atts['tabindex'] = $tag->get_option('tabindex', 'signed_int', true);

        if ($tag->is_required()) {
            $atts['aria-required'] = 'true';
        }

        $atts['aria-invalid'] = $validation_error ? 'true' : 'false';
        $atts['type'] = 'file';
        $atts['name'] = $tag->name;

        return $this->template(
            sanitize_html_class($tag->name),
            wpcf7_format_atts($atts),
            $validation_error
        );
    }

    /**
     * Load assets.
     */
    public function load_enqueue_script()
    {
        wp_enqueue_script(
            self::NAME_HANDLE.'-libs',
            plugins_url('frontend/assets/libs.js', __DIR__),
            null,
            YR3K_UPLOAD_VERSION,
            true
        );

        wp_enqueue_script(
            self::NAME_HANDLE,
            plugins_url('frontend/assets/script.js', __DIR__),
            ['jquery', self::NAME_HANDLE.'-libs'],
            YR3K_UPLOAD_VERSION,
            true
        );

        wp_enqueue_script(
            'init',
            plugins_url('frontend/assets/init.js', __DIR__),
            ['jquery', self::NAME_HANDLE.'-libs', self::NAME_HANDLE],
            YR3K_UPLOAD_VERSION,
            true
        );

        $targetSize = get_option('yr-images-optimize-upload-targetSize');
        $quality = get_option('yr-images-optimize-upload-quality');
        $minQuality = get_option('yr-images-optimize-upload-minQuality');
        $qualityStepSize = get_option('yr-images-optimize-upload-qualityStepSize');
        $maxWidth = get_option('yr-images-optimize-upload-maxWidth');
        $maxHeight = get_option('yr-images-optimize-upload-maxHeight');
        $resize = get_option('yr-images-optimize-upload-resize');
        $throwIfSizeNotReached = get_option('yr-images-optimize-upload-throwIfSizeNotReached');
        $maxFiles = (int) get_option('yr-images-optimize-upload-maxFiles', 3);
        $textError = _n('Maximum %d image is allowed.', 'Maximum %d images is allowed.', $maxFiles, YR3K_UPLOAD_REGISTRATION_NAME);
        $textError = sprintf($textError, $maxFiles);

        wp_localize_script(
            self::NAME_HANDLE,
            'YR3K_UPLOADER_OPTIONS',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'targetSize' => $targetSize ? $targetSize : 0.25,
                'quality' => $quality ? $quality : 0.75,
                'minQuality' => $minQuality ? $minQuality : 0.5,
                'qualityStepSize' => $qualityStepSize ? $qualityStepSize : 0.1,
                'maxWidth' => $maxWidth ? $maxWidth : 1920,
                'maxHeight' => $maxHeight ? $maxHeight : 1920,
                'resize' => $resize ? $resize : 1,
                'throwIfSizeNotReached' => $throwIfSizeNotReached ? $throwIfSizeNotReached : 0,
                'formatFile' => YR3K_UPLOAD_TYPE_FILES,
                'maxFile' => $maxFiles,
				'templatePreview' => get_option('yr-images-optimize-upload-template', Yr3kUploaderSettings::getTemplatePreview()),
                'templateDndArea' => get_option('yr-images-optimize-upload-template-dnd', Yr3kUploaderSettings::getTemplateDndArea()),
                'language' => [
                    'dnd_error_max_files' => $textError,
                    'info_file_origin' => __('Original size', YR3K_UPLOAD_REGISTRATION_NAME),
                    'info_file_compress' => __('Compressed', YR3K_UPLOAD_REGISTRATION_NAME),
                    'info_file_delete' => __('Delete', YR3K_UPLOAD_REGISTRATION_NAME),
                    'wrong_format' => __('Wrong file format', YR3K_UPLOAD_REGISTRATION_NAME),
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

    /**
     * Check if the shortcode is in the form.
     *
     * @param $fields
     *
     * @return bool
     */
    protected function isOptimizeFiled($fields)
    {
        foreach ($fields as $field) {
            if (YR3K_UPLOAD_SHORTCODE === $field->basetype) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get html for the shortcode on frontend.
     *
     * @param $name
     * @param $attrs
     * @param $error
     *
     * @return string
     */
    protected function template($name, $attrs, $error)
    {
        $template = '<span class="wpcf7-form-control-wrap %1$s wpcf7-images-optimize-upload-wrap">'
            .'<input %2$s multiple="multiple" accept="image/png,image/jpeg"/>'
            .'%3$s'
            .'</span>'
        ;

        return sprintf(
            $template,
            $name,
            $attrs,
            $error
        );
    }
}
