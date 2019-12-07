<?php

class Yr3kUploaderSettings
{
    const TMPL_PREVIEW = '<span>{{photoName}}</span> <span>{{txtInfoFileOrigin}}: {{beforeSize}}Mb ({{startWidth}}x{{startHeight}})</span> <span>{{txtInfoFileCompress}}: {{afterSize}}Mb ({{endWidth}}x{{endHeight}})</span> <del data-note="{{txtDelete}}">&times;</del>';

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
}
