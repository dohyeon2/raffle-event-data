<?php
//발표 데이터
new raffle_event_custom_post([
    "register_options" => [
        "show_in_menu" => "raffle",
        "taxonomies" => ["category"],
        "hierarchical" => true,
        "supports" => [
            "title",
            "editor",
            "thumbnail",
            "author"
        ]
    ],
    "post_type_name" => "raffle_announce",
    "post_label_name" => "NFT 발표"
], [
    "custom_box_html" => function ($post) {
        extract((array)$post);
        $raffle_user_list = json_decode(get_post_meta($ID, "raffle_users", true));
        //당첨된 유저 sorting
        usort($raffle_user_list, function ($a, $b) {
            if (!(bool)$b->user_name) {
                return  -1;
            } else {
                return 1;
            }
        });
        ob_start();
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">캡션</th>
                <td>
                    <input type="text" name="caption" value="<?= get_post_meta($ID, "caption", true) ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">당첨자들</th>
                <td>
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th scope="col">NFT</th>
                                <th scope="col">닉네임</th>
                                <th scope="col">지갑주소</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $format = "<tr><td>%s</td><td>%s</td><td>%s</td></tr>";
                            foreach ($raffle_user_list as $value) {
                                $value = (array)$value;
                                $value["user_name"] = (bool)$value["user_name"] ? $value["user_name"] : "당첨자 없음";
                                $value["nft_wallet"] = (bool)$value["nft_wallet"] ? $value["nft_wallet"] : "당첨자 없음";
                                echo sprintf($format, $value["nft_name"], $value["user_name"], $value["nft_wallet"]);
                            }
                            ?>
                        </tbody>
                    </table>
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
        $list = ["caption"];
        foreach ($list as $value) {
            if (key_exists($value, $_POST)) {
                update_post_meta($post_id, $value, $_POST[$value]);
            }
        }
    },
], [], []);