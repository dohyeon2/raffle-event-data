<?php
//nft그룹 데이터
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
    "post_type_name" => "raffle_nft_group",
    "post_label_name" => "NFT 그룹"
], [
    "custom_box_html" => function ($post) {
        extract((array)$post);
        extract(array_map(function ($v) {
            return $v[0];
        }, get_post_meta($ID)));
        ob_start();
        $nft_list = @unserialize($nft_list) ?: [];
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">이미지 업로드</th>
                <td>
                    <div class="img_container" style="margin-bottom:10px;">
                        <div class="char-img img-thumb" style="
                            width:100px;
                            height:100px;
                            background-size:contain;
                            background-position:center;
                            background-repeat:no-repeat;
                            background-color:#efefef;
                            border:1px solid #000;
                            background-image:url('<?= wp_get_attachment_url(@$char_img) ?>');
                        "></div>
                        <?= @$char_img ? '<input type="hidden" name="char_img" value="' . $char_img . '"/>' : "" ?>
                        <a data-type="char" data-label="캐릭터 이미지를 업로드 합니다." class="media-button button button-small button-primary">캐릭터 이미지 업로드</a>
                    </div>
                    <div class="img_container">
                        <div class="bg-img img-thumb" style="
                            width:300px;
                            height:100px;
                            background-size:contain;
                            background-position:center;
                            background-repeat:no-repeat;
                            background-color:#efefef;
                            border:1px solid #000;
                            background-image:url('<?= wp_get_attachment_url(@$bg_img) ?>');
                        "></div>
                        <?= @$bg_img ? '<input type="hidden" name="bg_img" value="' . $bg_img . '"/>' : "" ?>
                        <a data-type="bg" data-label="배경 이미지를 업로드 합니다." class="media-button button button-small button-secondary">배경 이미지 업로드</a>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row">분양 nft 리스트</th>
                <td>
                    <input type="text" id="nft_list_input" list="nft_list">
                    <button type="button" id="nft_list_input_button" class="button button-primary">등록하기</button>
                    <script>
                        const nft_lists = [<?= implode(",", array_map(function ($x) {
                                                return $x = "'" . $x . "'";
                                            }, $nft_list ?: [])) ?>];
                    </script>
                    <datalist id="nft_list">
                        <?php
                        (function () {
                            $nft_list = "";
                            global $wpdb;
                            $data_list = $wpdb->get_results("SELECT DISTINCT 
                                meta.meta_value, thumb.ID, thumb.post_title AS 'title'
                                FROM
                                (SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_value = 'nft_type') AS meta,
                                (SELECT ID, post_type, post_title FROM $wpdb->posts WHERE post_type = 'attachment') AS thumb
                                WHERE 
                                meta.post_id = thumb.ID
                                ", ARRAY_A);
                            foreach ($data_list as $value) {
                                $value['title'] = $value['title'] === "budong" ? "부동" : $value['title'];
                                $nft_list .= "<option value=\"$value[title] (media:$value[ID])\"/>";
                            }
                            echo $nft_list;
                        })();
                        (function () {
                            $nft_list = "";
                            foreach (get_posts([
                                "nopaging" => true,
                                "post_type" => "nft_data"
                            ]) as $value) {
                                $nft_list .= "<option value=\"$value->post_title ($value->ID)\"/>";
                            }
                            echo $nft_list;
                        })();
                        ?>
                    </datalist>
                    <hr>
                    <div id="added_nft_list">

                    </div>
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
        $list = ["nft_list", "bg_img", "char_img"];
        foreach ($list as $value) {
            if (key_exists($value, $_POST)) {
                update_post_meta($post_id, $value, $_POST[$value]);
            }
            if ($value === "nft_list" && @$_POST[$value][0] === "clear") {
                delete_post_meta($post_id, $value);
            }
        }
    }
], [],  [
    "raffle_nft_group_metadata" => [function ($arr, $post) {
        //nft 그룹의 메타데이터 response 수정
        $arr = get_post_meta($post['id']);
        @$arr["char_img"] = @$arr["char_img"] ? wp_get_attachment_url($arr["char_img"][0]) : "";
        @$arr["bg_img"] = @$arr["bg_img"] ? wp_get_attachment_url($arr["bg_img"][0]) : "";
        $arr["event"] = get_post($post['id'])->post_parent;
        $arr["event_due_type"] = get_post_meta($arr["event"], "due_type", true);
        $nft_list = get_post_meta($post["id"], "nft_list", true);
        $arr["nft_list"] = array_map(function ($x, $i) {
            preg_match("/-.*?:?(\d+)/", $x, $match);
            $post_id = $match[1];
            $post = get_post($match[1]);
            $user_participated = in_array(wp_get_current_user()->ID, get_post_meta($post_id, "participants_list") ?: []);
            return [
                "ID" => $match[1],
                "number" => $i + 1,
                "user_participated" => $user_participated,
                "participants_list" => get_post_meta($post_id, "participants_list"),
                "participants" => count(get_post_meta($post_id, "participants_list") ?: 0),
                "post_title" => $post->post_title,
            ];
        }, $nft_list, array_keys($nft_list));
        $pariticipants_list =  array_reduce($arr["nft_list"], function ($acc, $curr) {
            return [...$acc, ...$curr["participants_list"]];
        }, []);
        $arr["participate_count"] = count($pariticipants_list);
        $arr["user_participate_count"] = array_reduce($arr["nft_list"], function ($acc, $curr) {
            return $acc + ($curr["user_participated"] ? 1 : 0);
        }, 0);
        $arr["user_can_participate"] = $arr["user_participate_count"] >= 10 ? false : true;
        return $arr;
    }, 10, 2],
]);
