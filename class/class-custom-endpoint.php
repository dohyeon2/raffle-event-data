<?php

class raffe_event_custom_endpoint
{
    public function __construct($option = [])
    {
        $this->option = [
            "methods" => "GET",
            "path" => "",
            "endpoint_callback" => function ($request) {
                return $request;
            },
        ];
        $this->option = array_merge($this->option, $option);
        add_action('rest_api_init', array($this, "regist_api"));
    }
    function endpoint_callback(WP_REST_Request $request)
    {
        return $this->option['endpoint_callback']($request);
    }
    function regist_api()
    {
        register_rest_route($this->option["namespace"], $this->option["path"], array(
            'methods' => $this->option["methods"],
            'callback' => array($this, 'endpoint_callback'),
            'permission_callback' => '__return_true'
        ));
    }
}
