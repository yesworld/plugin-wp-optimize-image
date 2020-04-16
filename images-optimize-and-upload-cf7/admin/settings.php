<?php

class Yr3kUploaderSettings
{

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
}
