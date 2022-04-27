<?php

class raffle_event_custom_post
{
    public function __construct($array = [], $callbacks = [])
    {
        $default = [
            "register_options" => [
                "labels" => [
                    'name'          => "",
                    'singular_name' => "",
                ],
                'public'      => true,
                'has_archive' => true,
                'capability_type' => 'post',
                'supports' => [],
            ]
        ];
        $defaultCallbacks = [
            "custom_box_html" => function () {
            },
            "save_postdata" => function () {
            },
            "type_posts_columns" => function ($columns) {
                return $columns;
            },
            "type_custom_columns" => function () {
            },
        ];
        $this->array = array_merge_recursive($default, $array);
        $this->callbacks = array_replace_recursive($defaultCallbacks, $callbacks);

        if (!isset($this->array['post_type_name'])) return new WP_Error();
        if (!isset($this->array["post_label_name"])) return new WP_Error();

        $this->array["register_options"]["labels"]["name"] = $this->array["post_label_name"];
        $this->array["register_options"]["labels"]["singular_name"] = $this->array["post_type_name"];

        //스크립트 등록
        add_action('init', array($this, "custom_post_type"), 10);
        add_action("current_screen", array($this, "current_screen"), 10, 1);
        add_action('add_meta_boxes', array($this, "add_custom_box"));
        add_action('save_post', array($this, "save_postdata"));

        //리스트 열 수정
        add_filter('manage_' . $this->array["post_type_name"] . '_posts_columns', array($this, "type_posts_columns"));
        add_action('manage_' . $this->array["post_type_name"] . '_posts_custom_column', array($this, "type_custom_columns"), 10, 2);
    }

    public function current_screen($curr_screen)
    {
        $post_type_name = $this->array['post_type_name'];
        if ($curr_screen->id === $post_type_name) {
            $js_name = $post_type_name . '';
            $js_name = TM_URI . '/assets/js/post_type/' . $js_name . '.js';
            if (file_exists(TM_PATH . '/assets/js/post_type/' . $post_type_name . '.js')) {
                wp_enqueue_script($js_name, $js_name, array("jquery", "jquery-ui-sortable"), false, true);
            }
        }
    }

    public function add_custom_box()
    {
        $post_type_name = $this->array['post_type_name'];
        $post_label_name = $this->array["post_label_name"];
        add_meta_box(
            $post_type_name . '_info',   // Unique ID
            $post_label_name . ' 정보',      // Box title
            array($this, 'custom_box_html'),  // Content callback, must be of type callable
            $post_type_name                          // Post type
        );
    }

    public function custom_box_html($post)
    {
        $this->callbacks['custom_box_html']($post);
    }

    public function save_postdata($post_id)
    {
        if (get_post($post_id)->post_type !== $this->array['post_type_name']) {
            return;
        }
        $this->callbacks['save_postdata']($post_id);
    }

    public function custom_post_type()
    {
        $post_type_name = $this->array['post_type_name'];
        register_post_type(
            $post_type_name,
            $this->array['register_options']
        );
    }

    public function type_posts_columns($columns)
    {
        $columns = $this->callbacks['type_posts_columns']($columns);
        return $columns;
    }

    public function type_custom_columns($column, $post_id)
    {
        $this->callbacks['type_custom_columns']($column, $post_id);
    }
}
