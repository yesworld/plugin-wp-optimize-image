<?php

class Yr3kUploaderSettings
{
    private const OPTION_HEIC_SERVER_PROCESSING = 'yr-images-optimize-upload-heic-server-processing';

    public function __construct()
    {
        // Hook language
        add_action('plugins_loaded', [$this, 'setting_language']);
    }

    /**
     * Initialize localization.
     */
    public function setting_language()
    {
        load_plugin_textdomain(YR3K_UPLOAD_REGISTRATION_NAME, false, YR3K_UPLOAD_REGISTRATION_NAME.'/languages');
    }

    static function getTemplatePreview() {
        return '<span>{{photoName}}</span> <span>{{txtInfoFileOrigin}}: {{beforeSize}}Mb ({{startWidth}}x{{startHeight}})</span> <span>{{txtInfoFileCompress}}: {{afterSize}}Mb ({{endWidth}}x{{endHeight}})</span> <del data-note="{{txtDelete}}">&times;</del>';
    }

    static function getTemplateDndArea() {
        return sprintf(
            '<h3>%s</h3><span>%s</span><div class="images-optimize-upload-button-wrap"><a class="images-optimize-upload-button" href="#">%s</a></div>',
            __('Drag & Drop Images Here', YR3K_UPLOAD_REGISTRATION_NAME),
            __('or', YR3K_UPLOAD_REGISTRATION_NAME),
            __('Browse Files', YR3K_UPLOAD_REGISTRATION_NAME)
        );
    }

    static function supportsHeicHeif()
    {
        return !empty(self::getSupportedHeicExtensions());
    }

    static function isHeicServerProcessingEnabled()
    {
        return 1 === (int) get_option(self::OPTION_HEIC_SERVER_PROCESSING, 0) && self::supportsHeicHeif();
    }

    static function getAllowedExtensions()
    {
        $extensions = array_unique(array_filter(array_map(function ($extension) {
            return strtolower(ltrim(trim($extension), '.'));
        }, explode('|', YR3K_UPLOAD_FILE_FORMATS))));

        // HEIC and HEIF require a server-side decoder. Do not let a manually
        // entered extension bypass the capability check.
        $extensions = array_diff($extensions, ['heic', 'heif']);

        if (self::isHeicServerProcessingEnabled()) {
            $extensions = array_merge($extensions, self::getSupportedHeicExtensions());
        }

        return array_values(array_unique($extensions));
    }

    static function getSupportedHeicExtensions()
    {
        if (!class_exists('Imagick')) {
            return [];
        }

        try {
            $formats = array_map('strtoupper', Imagick::queryFormats());
        } catch (Exception $exception) {
            return [];
        }

        $extensions = [];
        if (in_array('HEIC', $formats, true)) {
            $extensions[] = 'heic';
        }
        if (in_array('HEIF', $formats, true)) {
            $extensions[] = 'heif';
        }

        return $extensions;
    }

    static function canProcessHeicExtension($extension)
    {
        return self::isHeicServerProcessingEnabled()
            && in_array(strtolower($extension), self::getSupportedHeicExtensions(), true);
    }

    static function getNumberOption($name, $default)
    {
        $value = get_option($name, $default);

        if ('' === $value || null === $value || !is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }
}
