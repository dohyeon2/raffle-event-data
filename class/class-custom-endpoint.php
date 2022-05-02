<?php
/**
 * 워드프레스 REST API 엔드포인트 추가용도  
 * 옵션의 내용에 맞게 입력하면 등록됨  
 * @package RaffleEvent_CustomEndpoint
 */
class RaffleEvent_CustomEndpoint
{
    /**
     * RaffleEvent_CustomEndpoint 클래스 생성자 
     * 인자로 받은 옵션을 인스턴스 변수에 저장함
     * 
     * @param array{
     *  methods: string,
     *  path: string,
     *  namespace: string,
     *  callback: function,
     *  permission_callback: __return_true
     *  } $option 옵션
     * @return void
     */
    public function __construct($option)
    {
        $this->option = [
            "methods" => "GET",
            "path" => "",
            "namespace" => "",
            "callback" => function ($request) {
                return $request;
            },
            'permission_callback' => '__return_true'
        ];
        $this->option = array_merge($this->option, $option);
        $this->fullpath = $this->option["namespace"] . $this->path["path"];
    }

    /**
     * 엔드포인트 콜백함수 확장  
     * 엔드포인트에서 리스폰스 이후 처리를 추가하기 위함
     * @return void
     */
    function endpoint_callback_extend($request)
    {
        /**
         * {endpoint_path}_pre_response_action
         * 리스폰스 처리 전에 액션 실행
         * arg1 = WP_REST_Request
         * arg2 = 해당 엔드포인트 인스턴스
         */
        do_action($this->fullpath . "_pre_response_action", $request, $this);

        /**
         * {endpoint_path}_response_filter
         * 처리된 리스폰스를 필터링
         * value = response
         * arg1 = WP_REST_Request
         * arg2 = 해당 엔드포인트 인스턴스
         */
        return apply_filters(
            $this->fullpath . "_response_filter",
            $this->option["callback"]($request),
            $request,
            $this
        );
    }

    /**
     * 엔드포인트 콜백 함수  
     * 생성자 인자로 받은 callback함수에 인자를 전달함
     * @param WP_REST_Request $request 워드프레스 리퀘스트 변수
     * @return void
     */
    function endpoint_callback(WP_REST_Request $request)
    {
        /**
         * {endpoint_path}_pre_request_action
         * 리퀘스트이전에 액션실행
         * arg1 = WP_REST_Request
         * arg2 = 해당 엔드포인트 인스턴스
         */
        do_action($this->fullpath . "_pre_request_action", $request, $this);

        /**
         * {endpoint_path}_request_filter
         * 리퀘스트이전에 필터실행
         * value = WP_REST_Request
         * arg1 = 해당 엔드포인트 인스턴스
         */
        $request = apply_filters(
            $this->fullpath . "_request_filter",
            $request,
            $this
        );
        return $this->endpoint_callback_extend($request);
    }

    /**
     * API등록 함수  
     * 인스턴스의 옵션 변수를 사용해 Wordpress의 rest route에 함수를 신규 등록함
     * @return void
     */
    function register_api()
    {
        register_rest_route(
            $this->option["namespace"],
            $this->option["path"],
            array(
                'methods' => $this->option["methods"],
                'callback' => array($this, 'endpoint_callback'),
                'permission_callback' => $this->option["permission_callback"]
            )
        );
    }
}

/**
 * Deprecated:다음버전에서 사라질 클래스
 * @see class/RaffleEvent_CustomEndpoint
 */
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
