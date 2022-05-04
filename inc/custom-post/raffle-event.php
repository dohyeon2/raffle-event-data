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
    "post_type_name" => "raffle_event_post",
    "post_label_name" => "래플 이벤트 포스트"
], [
    "passed_variables" => function ($post) {
        return ["test" => "test"];
    },
    "custom_box_html" => function ($post) {
        extract((array)$post);
        extract(array_map(function ($v) {
            return $v[0];
        }, get_post_meta($ID)));
        ob_start();
        $nft_list = @unserialize($nft_list) ?: [];
        $participants = null;
        $duplication = @$duplication ?: "0";
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">중복참가</th>
                <td>
                    현재상태:<a id="duplication-button" class="button button-primary">
                        <?= $duplication === "0" ? "중복금지" : "중복허용" ?>
                    </a>
                    <input type="hidden" name="duplication" value="<?= $duplication ?>" />
                </td>
            </tr>
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
                <th scope="row" class="set-start-date">시작 일시 설정</th>
                <td class="set-start-date">
                    <input type="date" name="start_date" value="<?= $start_date ?: date("Y-m-d") ?>" required />
                    <input type="time" name="start_time" value="<?= $start_time ?: date("H:i") ?>" required />
                </td>
            </tr>
            <tr>
                <th scope="row">만료타입 설정</th>
                <td>
                    <fieldset>
                        <label for="due-date">
                            <input name="due_type" type="radio" id="due-date" value="date" <?= @$due_type ? ($due_type === "date" ? "checked" : "") : "checked" ?> required>
                            기한 만료시까지
                        </label>
                        &nbsp;|&nbsp;
                        <label for="due-full">
                            <input name="due_type" type="radio" id="due-full" value="full" <?= @$due_type === "full" ? "checked" : "" ?> required>
                            선착순
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr class="set-date">
                <th scope="row">만료 기한 설정</th>
                <td>
                    <input type="date" name="end_date" value="<?= $end_date ?: "" ?>" />
                    <input type="time" name="end_time" value="<?= $end_time ?: "" ?>" />
                </td>
            </tr>
            <tr class="set-full">
                <th scope="row">만료 인원 설정</th>
                <td>
                    <input type="number" name="full_count" value="<?= $full_count ?: 1 ?>" />
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
            <tr>
                <th scope="row">NFT 그룹 리스트</th>
                <td>
                    <input type="text" id="nft_group_title" list="nft_group_list">
                    <button id="nft_group_input_button" class="button button-primary">등록하기</button>
                    <div class="input_status">

                    </div>
                    <datalist id="nft_group_list">
                        <?php
                        (function () {
                            $nft_type_list = get_posts([
                                "post_type" => "attachment",
                                "nopaging" => true,
                                "meta_key" => "data_type",
                                "meta_value" => "nft_type"
                            ]);
                            ob_start();
                            foreach ($nft_type_list as $key => $value) {
                                if ($value->post_title === "budong") {
                                    $value->post_title = "부동";
                                }
                                $value->post_title = str_replace("_", " ", $value->post_title);
                        ?>
                                <option><?= $value->post_title ?></option>
                        <?php
                            }
                            echo ob_get_clean();
                        })();
                        ?>
                    </datalist>
                    <hr />
                    <div id="nft-group-item-list">
                        <?php
                        (function ($ID) {
                            ob_start();
                        ?>
                            <script>
                                const groupList = [<?php
                                                    $group_items = get_posts([
                                                        "post_type" => "raffle_nft_group",
                                                        "post_parent" => $ID,
                                                        "nopaging" => true,
                                                    ]);
                                                    echo implode(",", array_map(
                                                        function ($value) {
                                                            return  json_encode([
                                                                "label" => $value->post_title,
                                                                "id" => $value->ID,
                                                            ]);
                                                        },
                                                        $group_items
                                                    ));
                                                    ?>];
                            </script>
                        <?php
                            echo ob_get_clean();
                        })($ID);
                        ?>
                    </div>
                </td>

            </tr>
            <tr class="condition">
                <th scope="row">조건 설정</th>
                <td>
                    <select name="condition">
                        <option value="-" <?= @$condition === "-" ? "selected" : "" ?>>-</option>
                        <option value="shark_in_mars" <?= @$condition === "shark_in_mars" ? "selected" : "" ?>>떡상어 보내기</option>
                    </select>
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
        $list = ["start_date", "start_time", "due_date", "due_time", "end_date", "end_time", "full_count", "due_type", "nft_list", "condition", "bg_img", "char_img", "duplication"];
        foreach ($list as $value) {
            if (key_exists($value, $_POST)) {
                update_post_meta($post_id, $value, $_POST[$value]);
            }
        }
        if (key_exists('start_date', $_POST) && key_exists('start_time', $_POST)) {
            update_post_meta($post_id, "start_time_int", strtotime($_POST['start_date'] . " " . $_POST['start_time']) + (HOUR_IN_SECONDS * 9));
        }
        if (key_exists('end_date', $_POST) && key_exists('end_time', $_POST)) {
            update_post_meta($post_id, "end_time_int", strtotime($_POST['end_date'] . " " . $_POST['end_time']) + (HOUR_IN_SECONDS * 9));
        }
    }
], [],  [
    "raffle_event_custom_post_metadata" => [function ($arr, $post) {
        if ($post["type"] === "raffle_event_post") {
            $event_id = $post["id"];
            $new_arr = [];
            foreach ($arr as $key => $value) {
                if ($v = @unserialize($value)) {
                    $x = $v;
                } else {
                    $x = $value;
                }
                $new_arr[$key] = $x;
            }
            $event_data = new RaffleEvent_EventData($post["id"]);
            $new_arr["nft_group_list"] = $event_data->get_nft_group_items();
            $new_arr["participants"] = 0;
            $new_arr["nft_list_ids"] = [];
            $new_arr["participants_list"]  = [];
            if (key_exists("nft_list", $new_arr)) {
                foreach ($new_arr["nft_list"] as $key => $value) {
                    preg_match("/-.*?:?(\d+)/", $value, $match);
                    $id = $match[1];
                    $new_arr["participants"] += count(get_post_meta($id, "participants_list")) ?: 0;
                    $new_arr["participants_list"] = array_merge($new_arr["participants_list"], get_post_meta($id, "participants_list"));
                    array_push($new_arr["nft_list_ids"], $id);
                }
            }
            $event_instance = new RaffleEvent_EventData($event_id);
            $event_instance->update_event_status();
            return $new_arr;
        }
        return $arr;
    }, 10, 2]
]);
