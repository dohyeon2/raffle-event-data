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
    "passed_variables"=>function($post){
        return ["test"=>"test"];
    },
    "custom_box_html" => function ($post) {
        extract((array)$post);
        extract(array_map(function ($v) {
            return $v[0];
        }, get_post_meta($ID)), 0, "meta_");
        ob_start();
        $nft_list = unserialize($nft_list);
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">참가자 수</th>
                <td>
                    <?= isset($participants) ? $participants : '0<input type="hidden" name="participants" value="0"/>' ?>
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
                    <input type="number" name="full_count" value="<?= $full_count ?: "" ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">분양 nft 리스트</th>
                <td>
                    <input type="text" id="nft_list_input" list="nft_list">
                    <button type="button" id="nft_list_input_button" class="button button-primary">등록하기</button>
                    <datalist id="nft_list">
                        <script>
                            const nft_lists = [<?=implode(",",array_map(function($x){
                                return $x = "'".$x."'";
                            },$nft_list))?>];
                            console.log(nft_lists);
                        </script>
                        <?php
                            (function(){
                                $nft_list = "";
                                foreach(get_posts([
                                    "nopaging"=>true,
                                    "post_type"=>"nft_data"
                                ]) as $value){
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
        $list = ["start_date", "start_time", "due_date", "due_time", "end_date", "end_time", "full_count", "due_type", "participants", "nft_list"];
        foreach ($list as $value) {
            if (key_exists($value, $_POST)) {
                update_post_meta($post_id, $value, $_POST[$value]);
            }
        }
        if (!key_exists('nft_list', $_POST)) {
            update_post_meta($post_id, "nft_list", []);
        }
        if (key_exists('start_date', $_POST) && key_exists('start_time', $_POST)) {
            update_post_meta($post_id, "start_time_int", strtotime($_POST['start_date'] . " " . $_POST['start_time']));
        }
        if (key_exists('end_date', $_POST) && key_exists('end_time', $_POST)) {
            update_post_meta($post_id, "end_time_int", strtotime($_POST['end_date'] . " " . $_POST['end_time']));
        }
    },
    "before_request_served_callback" => function ($result, $request, $server, $array) {
        $post_type_name = $array["post_type_name"];
        $time = time() + (HOUR_IN_SECONDS * 9);
        if (preg_match("/\/" . $post_type_name . "\/?/", $request->get_route(), $match)) {
            global $wpdb;
            $ended_posts = $wpdb->get_results("SELECT DISTINCT
                posts.ID 
            FROM 
                {$wpdb->prefix}posts AS posts,
                {$wpdb->prefix}postmeta AS meta1,
                {$wpdb->prefix}postmeta AS meta2,
                {$wpdb->prefix}postmeta AS meta3,
                {$wpdb->prefix}postmeta AS meta4
            WHERE
                posts.post_type = '$post_type_name' AND
                posts.ID = meta1.post_id AND
                posts.ID = meta2.post_id AND
                posts.ID = meta3.post_id AND
                posts.ID = meta4.post_id AND (
                    (
                        (meta4.meta_key = 'start_time_int' AND cast(meta4.meta_value as UNSIGNED) <= cast($time as UNSIGNED)) AND
                        meta3.meta_key = 'participants' AND
                        (meta1.meta_key = 'due_type' AND meta1.meta_value = 'full') AND
                        (meta2.meta_key = 'full_count' AND meta2.meta_value <= meta3.meta_value)
                    ) OR
                    (   
                        (meta1.meta_key = 'due_type' AND meta1.meta_value = 'date') AND
                        (meta2.meta_key = 'end_time_int' AND cast(meta2.meta_value as UNSIGNED) <= cast($time as UNSIGNED)) 
                    )
                )
            ", OBJECT);
            $upcomming_post = $wpdb->get_results("SELECT DISTINCT
                posts.ID
            FROM 
                {$wpdb->prefix}posts AS posts,
                {$wpdb->prefix}postmeta AS meta1
            WHERE
                posts.post_type = '$post_type_name' AND
                posts.ID = meta1.post_id AND
                (   
                    (meta1.meta_key = 'start_time_int' AND  cast(meta1.meta_value as UNSIGNED) >= cast($time as UNSIGNED))
                )
            ", OBJECT);
            $proceed_post = $wpdb->get_results("SELECT DISTINCT
                posts.ID 
            FROM 
                {$wpdb->prefix}posts AS posts,
                {$wpdb->prefix}postmeta AS meta1,
                {$wpdb->prefix}postmeta AS meta2,
                {$wpdb->prefix}postmeta AS meta3,
                {$wpdb->prefix}postmeta AS meta4
            WHERE
                posts.post_type = '$post_type_name' AND
                posts.ID = meta1.post_id AND
                posts.ID = meta2.post_id AND
                posts.ID = meta3.post_id AND
                posts.ID = meta4.post_id AND
                (
                    (
                        (meta4.meta_key = 'start_time_int' AND cast(meta4.meta_value as UNSIGNED) <= cast($time as UNSIGNED)) AND
                        meta3.meta_key = 'participants' AND
                        (meta1.meta_key = 'due_type' AND meta1.meta_value = 'full') AND
                        (meta2.meta_key = 'full_count' AND meta2.meta_value > meta3.meta_value)
                    ) OR
                    (   
                        (meta1.meta_key = 'due_type' AND meta1.meta_value = 'date') AND
                        (meta2.meta_key = 'end_time_int' AND cast(meta2.meta_value as UNSIGNED) >= cast($time as UNSIGNED)) AND
                        (meta3.meta_key = 'start_time_int' AND cast(meta3.meta_value as UNSIGNED) <= cast($time as UNSIGNED))
                    )
                )
            ", OBJECT);

            foreach ($upcomming_post as $value) {
                wp_update_post([
                    "ID" => $value->ID,
                    "post_category" => [get_category_by_slug('raffle-event-upcomming')->term_id]
                ]);
            }
            foreach ($ended_posts as $value) {
                wp_update_post([
                    "ID" => $value->ID,
                    "post_category" => [get_category_by_slug('raffle-event-end')->term_id]
                ]);
            }
            foreach ($proceed_post as $value) {
                wp_update_post([
                    "ID" => $value->ID,
                    "post_category" => [get_category_by_slug('raffle-event-proceed')->term_id]
                ]);
            }
        }
    }
]);
