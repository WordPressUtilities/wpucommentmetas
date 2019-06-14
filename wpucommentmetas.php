<?php

/*
Plugin Name: WPU Comment Metas
Plugin URI: https://github.com/WordPressUtilities/wpucommentmetas
Description: Simple admin for comments metas
Version: 0.2.1
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
            if (!isset($field['required'])) {
                $this->fields[$id]['required'] = false;
            }
            if (!isset($field['admin_visible'])) {
                $this->fields[$id]['admin_visible'] = true;
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
    }

    public function load_fields_front($fields = array()) {
        $current_filter = current_filter();
        foreach ($this->fields as $id => $field) {
            if (!in_array($current_filter, $field['display_hooks'])) {
                continue;
            }
            echo '<p class="comment-form-' . $id . '">' .
                '<label for="' . $id . '">' . $field['label'] . ($field['required'] ? ' <span class="required">*</span>' : '') . '</label> ' .
                '<input id="' . $id . '" name="wpucommentmetas__' . $id . '" type="text" value="" size="30" maxlength="245"' . ($field['required'] ? " required='required'" : '') . ' />' .
                '</p>';
        }
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
        $extra_comment_text = '';
        foreach ($this->fields as $id => $field) {
            if(!$field['admin_visible']){
                continue;
            }
            $meta_value = get_comment_meta($comment->comment_ID, $id, 1);
            $extra_comment_text .= '<strong>' . $field['label'] . ' : </strong> ' . $meta_value . "\n";
        }
        return $comment_text . '<hr />' . wpautop(trim($extra_comment_text));
    }

    /* ----------------------------------------------------------
      Save values
    ---------------------------------------------------------- */

    public function save_fields_form() {
        add_action('comment_post', array(&$this, 'save_comment_fields'), 10, 3);
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
