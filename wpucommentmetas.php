<?php

/*
Plugin Name: WPU Comment Metas
Plugin URI: https://github.com/WordPressUtilities/wpucommentmetas
Description: Simple admin for comments metas
Version: 0.7.0
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

defined('ABSPATH') or die(':(');

class WPUCommentMetas {
    private $plugin_version = '0.7.0';
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
        $this->display_admin_columns();
        add_action('admin_enqueue_scripts', array(&$this, 'admin_style'));
        add_action('admin_init', array(&$this, 'admin_edit_values'));
    }

    /* ----------------------------------------------------------
      Admin style
    ---------------------------------------------------------- */

    function admin_style() {
        wp_enqueue_style('admin-styles', plugins_url('assets/admin.css', __FILE__), array(), $this->plugin_version, 'all');
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
            if (!isset($field['admin_label'])) {
                $this->fields[$id]['admin_label'] = $this->fields[$id]['label'];
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
            if (!isset($field['admin_column'])) {
                $this->fields[$id]['admin_column'] = false;
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
            $extra_comment_text .= '<strong>' . $field['admin_label'] . ' : </strong> ' . $this->get_admin_comment_meta($comment->comment_ID, $id) . "\n";
        }
        $extra_comment_text = trim($extra_comment_text);
        if (!empty($extra_comment_text)) {
            $comment_text .= '<hr />' . wpautop($extra_comment_text);
        }
        return $comment_text;
    }

    function get_admin_comment_meta($comment_id, $meta_key) {
        $meta_value = get_comment_meta($comment_id, $meta_key, 1);
        $display_value = $meta_value;
        switch ($this->fields[$meta_key]['type']) {
        case 'checkbox':
            $display_value = '<span class="wpucommentmetas__checkbox">' . ($display_value == '1' ? '<span class="dashicons dashicons-yes"></span>' : '') . '</span>';
            break;
        default:
            $display_value = strip_tags($meta_value);

        }
        $display_value = apply_filters('wpucommentmetas__get_admin_comment_meta', $display_value, $meta_value, $meta_key, $comment_id);
        return $display_value;
    }

    /* ----------------------------------------------------------
      Display in columns
    ---------------------------------------------------------- */

    public function display_admin_columns() {
        add_filter('manage_edit-comments_columns', array(&$this, 'add_comments_columns'));
        add_action('manage_comments_custom_column', array(&$this, 'add_comment_columns_content'), 10, 2);
    }

    public function add_comments_columns($columns) {
        $new_columns = array();
        foreach ($this->fields as $id => $field) {
            if (!$field['admin_column']) {
                continue;
            }
            $new_columns['wpu__' . $id] = $field['admin_label'];
        }
        $columns = array_slice($columns, 0, 3, true) + $new_columns + array_slice($columns, 3, NULL, true);
        return $columns;
    }

    function add_comment_columns_content($column, $comment_ID) {
        global $comment;
        foreach ($this->fields as $id => $field) {
            if ($column != 'wpu__' . $id) {
                continue;
            }
            if (!$field['admin_column']) {
                continue;
            }
            echo $this->get_admin_comment_meta($comment_ID, $id);
        }
    }

    /* ----------------------------------------------------------
      Edit values
    ---------------------------------------------------------- */

    public function admin_edit_values() {
        add_meta_box('wpucommentmetas__editcomments', __('Extra', 'wpucommentmetas'), array(&$this, 'edit_metabox'), 'comment', 'normal');
        add_filter('edit_comment', array(&$this, 'save_comment_data'));
    }

    public function edit_metabox($comment) {
        if (empty($this->fields)) {
            return;
        }
        $html_table = '';
        foreach ($this->fields as $id => $field) {
            $field_id = 'wpucommentmetas__field__' . $id;
            $value = get_comment_meta($comment->comment_ID, $id, 1);
            $html_table .= '<tr valign="top"><td class="first"><label for="' . esc_attr($field_id) . '">' . $field['admin_label'] . '</label></td><td>';
            $html_table .= '<input type="text" id="' . $field_id . '" name="' . $field_id . '" value="' . esc_attr($value) . '" />';
            $html_table .= '</td></tr>';
        }
        echo '<table class="form-table editcomment comment_xtra"><tbody>' . $html_table . '</tbody></table>';
    }

    function save_comment_data($comment_ID) {

        foreach ($this->fields as $id => $field) {
            $field_id = 'wpucommentmetas__field__' . $id;
            if (!isset($_POST[$field_id])) {
                continue;
            }
            $post_value = $this->prepare_field_value($id, $field, $_POST[$field_id]);
            update_comment_meta($comment_ID, $id, $post_value);
        }

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
            if (!isset($field['display_hooks']) || empty($field['display_hooks'])) {
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
            $post_value = $this->prepare_field_value($id, $field, $_POST['wpucommentmetas__' . $id]);
            add_comment_meta($comment_id, $id, $post_value);
        }
    }

    /* ----------------------------------------------------------
      Filter value content
    ---------------------------------------------------------- */

    public function prepare_field_value($field_id, $field, $value) {
        $return = false;
        switch ($field['type']) {
        case 'checkbox':
            $return = $return == 1 ? $return : 0;
            break;
        default:
            $return = sanitize_text_field($value);
        }
        return $return;
    }

}

$WPUCommentMetas = new WPUCommentMetas();
