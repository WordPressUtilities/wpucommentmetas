<?php

/*
Plugin Name: WPU Comment Metas
Plugin URI: https://github.com/WordPressUtilities/wpucommentmetas
Description: Simple admin for comments metas
Version: 0.1.0
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
        }
    }

    /* ----------------------------------------------------------
      Display in front
    ---------------------------------------------------------- */

    public function display_fields_front() {
        add_filter('comment_form_default_fields', array(&$this, 'load_fields_front'), 10, 1);
    }

    public function load_fields_front($fields) {
        foreach ($this->fields as $id => $field) {
            $fields[$id] = '<p class="comment-form-' . $id . '">' .
                '<label for="' . $id . '">' . $field['label'] . ($field['required'] ? ' <span class="required">*</span>' : '') . '</label> ' .
                '<input id="' . $id . '" name="wpucommentmetas__' . $id . '" type="text" value="" size="30" maxlength="245"' . ($field['required'] ? " required='required'" : '') . ' />' .
                '</p>';
        }

        return $fields;
    }

    /* ----------------------------------------------------------
      Save values
    ---------------------------------------------------------- */

    public function save_fields_form() {
        add_action('comment_post', array(&$this, 'save_comment_fields'), 10, 1);
    }

    public function save_comment_fields($comment_id) {
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
