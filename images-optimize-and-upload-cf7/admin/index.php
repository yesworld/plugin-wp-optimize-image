<?php

class Yr3kUploaderButtonTagCF7
{
    private const BUTTON_POSITION = 55;

    public function __construct()
    {
        // add bottom tag to cf7
        add_action('wpcf7_admin_init', [$this, 'addButton'], self::BUTTON_POSITION);
    }

    /**
     * Add a button to the tags at the contact form 7.
     */
    public function addButton()
    {
        $tag_generator = WPCF7_TagGenerator::get_instance();
        $tag_generator->add(
            'images-optimize-upload',
            'optimize & upload',
            [$this, 'template']
        );
    }

    /**
     * Generate modal window for contact form 7 shortcode generator.
     *
     * @param WPCF7_ContactForm $contact_form_7
     * @param string            $args
     */
    public function template(WPCF7_ContactForm $contact_form_7, $args = '')
    {
        $args = wp_parse_args($args, []);

        $description = __('Generate a form-tag for an Image Optimize & Upload field. For more details, see %s.', YR3K_UPLOAD_REGISTRATION_NAME);
        $desc_link = wpcf7_link(
            __('https://github.com/yesworld', YR3K_UPLOAD_REGISTRATION_NAME),
            __('Images Optimize & Upload on GitHub', YR3K_UPLOAD_REGISTRATION_NAME)
        );

        $legend = sprintf(esc_html($description), $desc_link);
        $type = YR3K_UPLOAD_SHORTCODE;
        $prefix = $args['content'];
        require_once YR3K_UPLOAD_PATH.'/views/form.tag.cf7.php';
    }
}

class Yr3kUploaderAdmin
{
    private const FIELD_NAME_SHOW_NOTICE = 'yr-images-optimize-upload-do-not-show-rating-tip';
    private $register = [
        'yr-images-optimize-upload-targetSize',
        'yr-images-optimize-upload-quality',
        'yr-images-optimize-upload-minQuality',
        'yr-images-optimize-upload-qualityStepSize',
        'yr-images-optimize-upload-maxWidth',
        'yr-images-optimize-upload-maxHeight',
        'yr-images-optimize-upload-resize',
        'yr-images-optimize-upload-throwIfSizeNotReached',
        'yr-images-optimize-upload-removeFileAfterSend',
        'yr-images-optimize-upload-maxFiles',
        'yr-images-optimize-upload-template-dnd',
        'yr-images-optimize-upload-template',
        self::FIELD_NAME_SHOW_NOTICE,
    ];

    public function __construct()
    {
        // init action
        add_action('admin_menu', [$this, 'addButton']);
        add_action('admin_init', [$this, 'admin_init']);

        // add the Settings link to the plugin activation form
        add_filter('plugin_action_links_' . YR3K_UPLOAD_BASENAME, [$this, 'addLink']);

        // Hook activation/deactivation plugin
        register_activation_hook(YR3K_UPLOAD_BASENAME, [$this, 'activate']);
        register_deactivation_hook(YR3K_UPLOAD_BASENAME, [$this, 'deactivate']);

        add_action('admin_notices', [$this, 'general_admin_notice']);
        add_action('admin_notices', [$this, 'rating_notice']);
    }

    /**
     * Display admin notice on settings page.
     */
    public function general_admin_notice()
    {
        if (isset($_GET['page']) && 'optimizer-3000' == $_GET['page'] && isset($_REQUEST['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>'.__('Settings saved.').'</p></div>';
        }
    }

    /**
     * Display admin notice on settings page about rating.
     */
    public function rating_notice()
    {
        $ratingOption = get_option(self::FIELD_NAME_SHOW_NOTICE, null);

        if ($ratingOption !== null && time() < $ratingOption) {
            return;
        }

        $queryAdminUrl = admin_url(basename($_SERVER['REQUEST_URI']));
        $queryAdminUrl .= strpos($queryAdminUrl,'?') !== false ? '&' : '?';
        $queryAdminUrl .= 'yr_rating_notice=';

        require_once YR3K_UPLOAD_PATH  .'/views/form.notice.php';
    }

    /**
     * Add Settings link to the plugins page.
     *
     * @param $links
     *
     * @return array
     */
    public function addLink($links)
    {
        return array_merge([
            '<a href="'.admin_url('admin.php?page=optimizer-3000').'">'.__('Settings').'</a>',
        ], $links);
    }

    /**
     * Add button to submenu menu Contact form 7.
     */
    public function addButton()
    {
        add_submenu_page(
            'wpcf7',
            'Images Optimize & Upload - Settings',
            'Optimize & Upload',
            'manage_options',
            'optimizer-3000',
            [$this, 'template']
        );
    }

    /**
     * Template for general settings.
     */
    public function template()
    {
        require_once YR3K_UPLOAD_PATH.'/views/form.settings.optimizer.php';
    }

    public function activate() {}

    /**
     * Remove options from DB and folder from the Server when plugin is deactivated.
     */
    public function deactivate()
    {
        if (file_exists(YR3K_UPLOAD_TEMP_DIR)) {
            $this->delTree(YR3K_UPLOAD_TEMP_DIR);
        }

        foreach ($this->register as $name) {
            delete_option($name);
        }
    }

    /**
     * Check all temporary folders and delete them.
     *
     * @param $dir
     *
     * @return bool
     */
    public function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }


    public function admin_init()
    {
        foreach ($this->register as $name) {
            register_setting(YR3K_UPLOAD_REGISTRATION_NAME, $name);
        }

        if (!isset($_GET['yr_rating_notice'])) {
            return;
        }

        $rate = intval($_GET['yr_rating_notice']);
        $time = $rate === 1 ? '+7 days' : '+3 years';

        update_option(self::FIELD_NAME_SHOW_NOTICE, strtotime($time));
    }
}
