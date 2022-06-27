<?php

/*
Plugin Name: WPU Comment Metas
Plugin URI: https://github.com/WordPressUtilities/wpucommentmetas
Description: Simple admin for comments metas
Version: 0.4.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

defined('ABSPATH') or die(':(');

class WPUCommentMetas {
    private $fields = array();
    public function __construct() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {
        load_plugin_textdomain('wpucommentmetas', false, dirname(plugin_basename(__FILE__)) . '/lang/');
        $this->set_fields();
        $this->display_fields_front();
        $this->display_fields_admin();
        $this->save_fields_form();
    }

    /* ----------------------------------------------------------
      Init main fields
    ---------------------------------------------------------- */

    public function set_fields() {
        $this->fields = apply_filters('wpucommentmetas_fields', $this->fields);
        foreach ($this->fields as $id => $field) {
            if (!is_array($field)) {
                $this->fields[$id] = array();
            }
            if (!isset($field['label'])) {
                $this->fields[$id]['label'] = ucfirst($id);
            }
            if (!isset($field['type'])) {
                $this->fields[$id]['type'] = 'text';
            }
            if (!isset($field['help'])) {
                $this->fields[$id]['help'] = '';
            }
            if (!isset($field['required'])) {
                $this->fields[$id]['required'] = false;
            }
            if (!isset($field['admin_visible'])) {
                $this->fields[$id]['admin_visible'] = true;
            }
            if (!isset($field['admin_list_visible'])) {
                $this->fields[$id]['admin_list_visible'] = false;
            }
            if (!isset($field['display_hooks'])) {
                $this->fields[$id]['display_hooks'] = array(
                    'comment_form_logged_in_after',
                    'comment_form_after_fields'
                );
            }
            if (!is_array($this->fields[$id]['display_hooks'])) {
                $this->fields[$id]['display_hooks'] = array($this->fields[$id]['display_hooks']);
            }
        }
    }

    /* ----------------------------------------------------------
      Display in front
    ---------------------------------------------------------- */

    public function display_fields_front() {
        add_action('comment_form_top', array(&$this, 'load_fields_front'));
        add_action('comment_form_before_fields', array(&$this, 'load_fields_front'));
        add_action('comment_form_logged_in_after', array(&$this, 'load_fields_front'));
        add_action('comment_form_after_fields', array(&$this, 'load_fields_front'));
        add_filter('comment_form_submit_field', function ($field, $args) {
            ob_start();
            $this->load_fields_front();
            $out = ob_get_clean();
            return $out . $field;
        }, 10, 2);
    }

    public function load_fields_front($fields = array()) {
        $current_filter = current_filter();
        foreach ($this->fields as $id => $field) {
            if (!in_array($current_filter, $field['display_hooks'])) {
                continue;
            }
            echo $this->load_field_html($id, $field);

        }
    }

    function load_field_html($id, $field) {
        $html = '';

        /* Label */
        $label_for = 'for="' . $id . '"';
        $label_text = $field['label'];
        if ($field['required']) {
            $label_text .= ' <span class="required">*</span>';
        }

        /* Input */
        $input_id_name = ' name="wpucommentmetas__' . $id . '" id="' . $id . '" ';
        if ($field['required']) {
            $input_id_name .= '  required="required"';
        }

        switch ($field['type']) {
        case 'checkbox':
            $html .= '<input ' . $input_id_name . ' type="checkbox" value="1" />';
            $html .= '<label ' . $label_for . '>' . $label_text . '</label> ';
            break;
        default:
            $html .= '<label ' . $label_for . '>' . $label_text . '</label> ';
            $html .= '<input ' . $input_id_name . ' type="text" value="" size="30" maxlength="245" />';
        }
        if ($field['help']) {
            $html .= '<span class="comment-field-help">' . $field['help'] . '</span>';
        }

        return '<p class="comment-form-' . $id . '">' . $html . '</p>';
    }

    /* ----------------------------------------------------------
      Display in admin
    ---------------------------------------------------------- */

    public function display_fields_admin() {
        if (is_admin()) {
            add_filter('comment_text', array($this, 'comment_text'), 10, 3);
        }
    }

    public function comment_text($comment_text, $comment, $args = array()) {
        $screen = get_current_screen();
        $is_edit_comment = false;
        if (!is_null($screen) && !is_wp_error($screen) && is_object($screen)) {
            $is_edit_comment = $screen->base == 'edit-comments';
        }
        $extra_comment_text = '';
        foreach ($this->fields as $id => $field) {
            if (!$field['admin_visible']) {
                continue;
            }
            if (!$field['admin_list_visible'] && $is_edit_comment) {
                continue;
            }
            $meta_value = get_comment_meta($comment->comment_ID, $id, 1);
            $extra_comment_text .= '<strong>' . $field['label'] . ' : </strong> ' . $meta_value . "\n";
        }
        $extra_comment_text = trim($extra_comment_text);
        if (!empty($extra_comment_text)) {
            $comment_text .= '<hr />' . wpautop($extra_comment_text);
        }
        return $comment_text;
    }

    /* ----------------------------------------------------------
      Save values
    ---------------------------------------------------------- */

    public function save_fields_form() {
        add_action('pre_comment_on_post', array(&$this, 'pre_comment_on_post'), 10, 1);
        add_action('comment_post', array(&$this, 'save_comment_fields'), 10, 3);
    }

    public function pre_comment_on_post($comment_post_ID) {
        foreach ($this->fields as $id => $field) {
            if ($field['type'] == 'checkbox') {
                continue;
            }
            if (!isset($_POST['wpucommentmetas__' . $id]) || !$_POST['wpucommentmetas__' . $id]) {
                wp_die(sprintf(__('The field "%s" is missing.', 'wpucommentmetas'), $field['label']));
            }
        }
    }

    public function save_comment_fields($comment_id, $comment_approved, $commentdata) {
        foreach ($this->fields as $id => $field) {
            if (!isset($_POST['wpucommentmetas__' . $id])) {
                continue;
            }
            $post_value = esc_html($_POST['wpucommentmetas__' . $id]);
            add_comment_meta($comment_id, $id, $post_value);
        }
    }

}

$WPUCommentMetas = new WPUCommentMetas();
