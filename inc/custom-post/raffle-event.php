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
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">참가자 수</th>
                <td>
                    <?= $participants ?: 0 ?><input type="hidden" name="participants" value="<?= $participants ?: 0 ?>" />
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
                            <input name="due_type" type="radio" id="due-date" value="date" <?= $due_type ? ($due_type === "date" ? "checked" : "") : "checked" ?> required>
                            기한 만료시까지
                        </label>
                        &nbsp;|&nbsp;
                        <label for="due-full">
                            <input name="due_type" type="radio" id="due-full" value="full" <?= $due_type === "full" ? "checked" : "" ?> required>
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
                            global $wpdb;
                            $data_list = $wpdb->get_results("SELECT DISTINCT 
                                meta.meta_value, thumb.ID 
                                FROM
                                $wpdb->posts AS posts,
                                $wpdb->postmeta AS meta,
                                $wpdb->posts AS thumb
                                WHERE 
                                thumb.post_type = 'attachment' AND
                                thumb.post_title LIKE REPLACE(meta.meta_value,' ','_') AND
                                meta.meta_key = 'apt_type' AND
                                posts.post_type = 'nft_data'
                                ", ARRAY_A);
                            foreach ($data_list as $value) {
                                if ($value["meta_value"] === NULL) {
                                    continue;
                                }
                                $nft_list .= "<option value=\"$value[meta_value] (media:$value[ID])\"/>";
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
            <tr class="condition">
                <th scope="row">조건 설정</th>
                <td>
                    <select name="condition">
                        <option value="-" <?= $condition === "-" ? "selected" : "" ?>>-</option>
                        <option value="shark_in_mars" <?= $condition === "shark_in_mars" ? "selected" : "" ?>>떡상어 보내기</option>
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
        $list = ["start_date", "start_time", "due_date", "due_time", "end_date", "end_time", "full_count", "due_type", "nft_list", "condition", "bg_img", "char_img"];
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
    },
    // "before_request_served_callback" => function ($result, $request, $server, $array) {
    //     $post_type_name = $array["post_type_name"];
    //     $time = time() + (HOUR_IN_SECONDS * 9);
    //     if (preg_match("/\/" . $post_type_name . "\/?/", $request->get_route(), $match)) {
    //         global $wpdb;
    //         $ended_posts = $wpdb->get_results("SELECT DISTINCT
    //             posts.ID 
    //         FROM 
    //             {$wpdb->prefix}posts AS posts,
    //             {$wpdb->prefix}postmeta AS meta1,
    //             {$wpdb->prefix}postmeta AS meta2,
    //             {$wpdb->prefix}postmeta AS meta3,
    //             {$wpdb->prefix}postmeta AS meta4
    //         WHERE
    //             posts.post_type = '$post_type_name' AND
    //             posts.ID = meta1.post_id AND
    //             posts.ID = meta2.post_id AND
    //             posts.ID = meta3.post_id AND
    //             posts.ID = meta4.post_id AND(
    //                 (   
    //                     (meta1.meta_key = 'due_type' AND meta1.meta_value = 'date') AND
    //                     (meta2.meta_key = 'end_time_int' AND cast(meta2.meta_value as UNSIGNED) <= cast($time as UNSIGNED)) 
    //                 )
    //             )
    //         ", OBJECT);
    //         $upcomming_post = $wpdb->get_results("SELECT DISTINCT
    //             posts.ID
    //         FROM 
    //             {$wpdb->prefix}posts AS posts,
    //             {$wpdb->prefix}postmeta AS meta1
    //         WHERE
    //             posts.post_type = '$post_type_name' AND
    //             posts.ID = meta1.post_id AND
    //             (   
    //                 (meta1.meta_key = 'start_time_int' AND  cast(meta1.meta_value as UNSIGNED) >= cast($time as UNSIGNED))
    //             )
    //         ", OBJECT);
    //         $proceed_post = $wpdb->get_results("SELECT DISTINCT
    //             posts.ID 
    //         FROM 
    //             {$wpdb->prefix}posts AS posts,
    //             {$wpdb->prefix}postmeta AS meta1,
    //             {$wpdb->prefix}postmeta AS meta2,
    //             {$wpdb->prefix}postmeta AS meta3,
    //             {$wpdb->prefix}postmeta AS meta4
    //         WHERE
    //             posts.post_type = '$post_type_name' AND
    //             posts.ID = meta1.post_id AND
    //             posts.ID = meta2.post_id AND
    //             posts.ID = meta3.post_id AND
    //             posts.ID = meta4.post_id AND
    //             (
    //                 (   
    //                     (meta1.meta_key = 'due_type' AND meta1.meta_value = 'date') AND
    //                     (meta2.meta_key = 'end_time_int' AND cast(meta2.meta_value as UNSIGNED) >= cast($time as UNSIGNED)) AND
    //                     (meta3.meta_key = 'start_time_int' AND cast(meta3.meta_value as UNSIGNED) <= cast($time as UNSIGNED))
    //                 )
    //             )
    //         ", OBJECT);

    //         foreach ($upcomming_post as $value) {
    //             wp_update_post([
    //                 "ID" => $value->ID,
    //                 "post_category" => [get_category_by_slug('raffle-event-upcomming')->term_id]
    //             ]);
    //         }
    //         foreach ($ended_posts as $value) {
    //             wp_update_post([
    //                 "ID" => $value->ID,
    //                 "post_category" => [get_category_by_slug('raffle-event-end')->term_id]
    //             ]);
    //         }
    //         foreach ($proceed_post as $value) {
    //             wp_update_post([
    //                 "ID" => $value->ID,
    //                 "post_category" => [get_category_by_slug('raffle-event-proceed')->term_id]
    //             ]);
    //         }
    //     }
    // }
], [],  [
    "raffle_event_custom_post_metadata" => [function ($arr, $post) {
        $new_arr = [];
        foreach ($arr as $key => $value) {
            if ($v = @unserialize($value)) {
                $x = $v;
            } else {
                $x = $value;
            }
            $new_arr[$key] = $x;
        }
        $new_arr["participants"] = 0;
        $new_arr["nft_list_ids"] = [];
        if (key_exists("nft_list", $new_arr)) {
            foreach ($new_arr["nft_list"] as $key => $value) {
                preg_match("/-.*?:?(\d+)/", $value, $match);
                $id = $match[1];
                $new_arr["participants"] += count(get_post_meta($id, "pariticipants_list")) ?: 0;
                array_push($new_arr["nft_list_ids"], $id);
            }
        }
        if (get_post_meta($post['id'], "due_type", true) === "full" && $new_arr["participants"] * 1 === get_post_meta($post['id'], "full_count", true) * 1) {
            wp_update_post([
                "ID" => $post['id'],
                "post_category" => [get_category_by_slug('raffle-event-end')->term_id]
            ]);
        } else {
            wp_update_post([
                "ID" => $post['id'],
                "post_category" => [get_category_by_slug('raffle-event-proceed')->term_id]
            ]);
        }
        return $new_arr;
    }, 10, 2]
]);
