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
    "custom_box_html" => function ($post) {
        extract((array)$post);
        ob_start();
?>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row" class="set-start-date">시작 일자 설정</th>
                <td class="set-start-date">
                    <input type="date" name="start-date" value="<?=date("Y-m-d")?>" />
                    <input type="time" name="start-time" />
                </td>
            </tr>
            <tr>
                <th scope="row">만료타입 설정</th>
                <td>
                    <fieldset>
                        <label for="due-date">
                            <input name="due-type" type="radio" id="due-date" value="date" checked>
                            기한 만료시까지
                        </label>
                        &nbsp;|&nbsp;
                        <label for="due-full">
                            <input name="due-type" type="radio" id="due-full" value="full">
                            선착순
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row" class="set-end-date">만료 기한 설정</th>
                <td class="set-end-date">
                    <input type="date" />
                </td>
                <th scope="row" class="set-full">만료 인원 설정</th>
                <td class="set-full">

                </td>
            </tr>
        </tbody>
    </table>
<?php
        $html = ob_get_clean();
        echo $html;
    }
]);
