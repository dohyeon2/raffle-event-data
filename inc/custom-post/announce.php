<?php
new raffle_event_custom_post([
    "register_options" => [
        "show_in_menu" => "raffle",
        "taxonomies" => ["category"],
        "supports" => [
            "title",
            "editor",
            "thumbnail"
        ]
    ],
    "post_type_name" => "raffle_announce",
    "post_label_name" => "NFT 발표"
], [
    "custom_box_html" => function ($post) {
        extract((array)$post);
        var_dump(get_post_meta($ID));
        ob_start();
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">발표 할 이벤트 선택</th>
                <td>
                    <?= (isset($participants) && is_array($participants)) ? count($participants) : '0' ?>
                </td>
            </tr>
        </tbody>
    </table>
<?php
        $html = ob_get_clean();
        echo $html;
    },
    "save_postdata" => function ($post_id) {
        date_default_timezone_set("Asia/Seoul");
        $list = [];
        foreach ($list as $value) {
            if (key_exists($value, $_POST)) {
                update_post_meta($post_id, $value, $_POST[$value]);
            }
        }
    },
], [], []);



add_action('rest_api_init', function () {
    register_rest_field("attachment", 'title', array(
        'get_callback' => function ($data) {
            if ($data['title']['rendered'] === "budong") {
                $data['title']['rendered'] = "부동";
            }
            return $data['title'];
        },
    ));
    register_rest_field("attachment", 'metadata', array(
        'get_callback' => function ($data) {
            $data = get_post_meta($data['id']);
            unset($data['wallet_address']);
            return $data;
        },
    ));
});
