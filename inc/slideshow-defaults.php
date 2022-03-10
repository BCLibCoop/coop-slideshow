<?php

/**
 * Plugin Name: Slideshow Defaults administration UI
 * Description: Global configuration of a slideshow environment within a blog.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.2.0
 *
 * @package   Slideshow Defaults
 * @copyright BC Libraries Coop 2013
 **/

namespace BCLibCoop;

class SlideshowDefaults
{
    private static $instance;
    private static $slug = 'slideshow';
    private $db_init = false;

    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        $this->db_init = get_option('_' . self::$slug . '_db_init');

        add_action('wp_ajax_coop-save-slideshow-change', [&$this, 'defaultsPageSave']);
        add_filter('attachment_fields_to_edit', [&$this, 'addRegionField'], 10, 2);
        add_filter('attachment_fields_to_save', [&$this, 'regionFieldSave'], 10, 2);

        add_action('admin_menu', [&$this, 'addSlideshowMenu']);
    }

    public function addSlideshowMenu()
    {
        // this is only for the Super Admins
        if (current_user_can('manage_network')) {
            add_submenu_page(
                'site-manager',
                'Slideshow Defaults',
                'Slideshow Defaults',
                'manage_local_site',
                'slides-manager',
                [&$this, 'defaultsPage']
            );
        }
    }
    /**
     * Store / adjust settings from the global Slideshow Settings long form of options.
     **/
    public function defaultsPage()
    {
        $out = [];
        $out[] = '<div class="wrap">';

        $out[] = '<h1 class="wp-heading-inline">Slideshow Settings</h1>';
        $out[] = '<hr class="wp-header-end">';

        $out[] = '<p>Change defaults for all slideshows on the site. Per-slideshow options can still override.</p>';

        $out[] = '<table class="form-table">';

        $out[] = $this->parseDefaults();

        $out[] = '</table>';

        $out[] = '<p class="submit">';
        $out[] = '<input type="submit" value="Save Changes" class="button button-primary" '
                 . 'id="coop-slideshow-settings-submit" name="submit">';
        $out[] = '</p>';

        echo implode("\n", $out);
    }

    /**
     * Process options posted back via AJAX. There's no sanitizing happening here
     * because some of these fields can contain javascript functions which are sure to
     * get mangled.
     */
    public function defaultsPageSave()
    {
        if (check_ajax_referer(self::$slug, false, false) === false) {
            wp_send_json([
                'result' => 'failed',
                'feedback' => 'Invalid security token, please reload and try again',
            ]);
        }

        foreach ($_POST['keys'] as $k) {
            $val = $_POST[$k];
            update_option('_' . self::$slug . '_' . $k, $val);
        }

        wp_send_json([
            'feedback' => count($_POST['keys']) . ' settings updated',
        ]);
    }

    public static function defaultsPublishConfig($echo = true)
    {
        $tag = '_' . self::$slug . '_';

        // Get all db options, quicker, probably already cached
        $alloptions = wp_load_alloptions();

        $out = [];
        if ($echo) {
            $out[] = '<script id="slideshow-settings">';
        }

        $out[] = 'window.slideshow_custom_settings = {';

        foreach ($alloptions as $name => $val) {
            // Just the slideshow options please
            if (strpos($name, $tag) !== 0) {
                continue;
            }

            // get the variable name by stripping the slug off the stored option_name
            $k = str_replace($tag, '', $name);

            if (is_numeric($val) || $val == 'true' || $val == 'false' || $val == 'undefined' || $val == 'null') {
                $v = $val;
            } else {
                // pass function defs thru unquoted
                if (false !== stripos($k, 'onSlide', 0)) {
                    $v = stripslashes($val);
                } else {
                    $v = sprintf("'%s'", $val);
                }
            }
            $out[] = sprintf("%s: %s,", $k, $v);
        }

        $out[] = '};';

        if ($echo) {
            $out[] = '</script>';
            echo implode("\n", $out);
            return;
        }

        return implode("\n", $out);
    }

    private function parseDefaults()
    {
        $lines = file(dirname(__FILE__) . '/default-settings.js');

        $all_defaults = [];
        $out = [];

        $_fmt = '<tr><th>%s</th><td>%s</td><td>%s</td></tr>';

        foreach ($lines as $l) {
            /// skip lines
            if (false !== strpos($l, '/*')) {
                continue;
            }
            if (false !== (strpos($l, ' ') == 0)) {
                continue;
            }

            // Headers
            if (false !== strpos($l, '//')) {
                list($j, $h) = explode('//', $l);
                $out[] = '<tr><th><h3>' . $h . '</h3></th><td>Current value</td><td>Default</td></tr>';
                continue;
            }

            list($t, $s) = explode(": ", rtrim($l, ",\n "));

            $widget = [];
            $_opt = get_option('_' . self::$slug . '_' . $t, null);
            $default = '';

            if (false !== strpos($s, ',')) {
                // multiple term radio
                $s = str_replace(['"', "'"], '', $s);
                $pcs = explode(',', $s);
                $default = $pcs[0];
                $selected = ($_opt !== null ? $_opt : $default);

                for ($i = 0; $i < count($pcs); $i++) {
                    $id = $t . $i;
                    $widget[] = sprintf(
                        '<input class="slideshow-default" type="radio" id="%s" name="%s" value="%s"%s>',
                        $id,
                        $t,
                        $pcs[$i],
                        checked($selected, $pcs[$i], false)
                    );
                    $widget[] = sprintf('<label for="%s">%s</label>', $id, $pcs[$i]);
                }
            } elseif (
                false !== strpos($s, 'true')
                || false !== strpos($s, 'false')
            ) {
                // binary radio t/f
                $default = (false !== strpos($s, 'true')) ? 'true' : 'false';
                $selected = ($_opt !== null ? $_opt : $default);

                $widget[] = sprintf('<input class="slideshow-default" type="radio" id="%s-t" name="%s" value="true"%s>', $t, $t, checked($selected, 'true', false));
                $widget[] = sprintf('<label for="%s-t">true</label>', $t);

                $widget[] = sprintf('<input class="slideshow-default" type="radio" id="%s-f" name="%s" value="false"%s>', $t, $t, checked($selected, 'false', false));
                $widget[] = sprintf('<label for="%s-f">false</label>', $t);
            } elseif (false !== strpos($s, "'")) {
                // quoted string value - strip quotes
                $default = str_replace(['"', "'"], '', $s);
                $selected = ($_opt !== null ? $_opt : $default);

                $widget[] = sprintf('<input class="slideshow-default" type="text" id="%s" name="%s" value="%s">', $t, $t, $selected);
            } else {
                // text field
                $default = $s;
                $selected = ($_opt !== null ? $_opt : $default);

                $widget[] = sprintf('<input class="slideshow-default" type="text" id="%s" name="%s" value="%s">', $t, $t, $selected);
            }

            $all_defaults[$t] = $default;

            $out[] = sprintf($_fmt, $t, implode("&nbsp;&nbsp;", $widget), $default);
        }

        if (empty($this->db_init) || $this->db_init == false) {
            foreach ($all_defaults as $term => $val) {
                update_option('_' . self::$slug . '_' . $term, $val);
            }

            update_option('_' . self::$slug . '_db_init', true);
        }

        return implode("\n", $out);
    }

    /**
     * Set & saver defaults for custom attachment field slide_region
     * for Shared Media site (network root) only
     **/

    public function addRegionField($form_fields, $post)
    {
        if (get_current_blog_id() === 1) {
            $provinces = array_merge(['' => ''], SlideshowManager::$media_sources);
            unset($provinces['local'], $provinces['shared']);

            $selected = get_post_meta($post->ID, 'slide_region', true);

            $inputname = "attachments[{$post->ID}][slide_region]";

            $form_fields['slide_region'] = [
                'label' => 'Slide Region',
                'input' => 'html',
                'html' => '<select name="' . $inputname . '" id="' . $inputname . '">',
                'helps' => 'Which province is this slide from?',
            ];

            foreach ($provinces as $slug => $province) {
                $form_fields['slide_region']['html'] .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    $slug,
                    selected($selected, $slug, false),
                    $province
                );
            }

            $form_fields['slide_region']['html'] .= '</select>';
        }

        return $form_fields;
    }

    public function regionFieldSave($post, $attachment)
    {
        if (isset($attachment['slide_region'])) {
            update_post_meta($post['ID'], 'slide_region', sanitize_text_field($attachment['slide_region']));
        }

        return $post;
    }
}
