<?php

class RaffleEvent_EventData
{
    //래플 이벤트 객체 클래스

    //상수선언
    const UPCOMMING_STATUS_SLUG = 'raffle-event-upcomming';
    const PROCEED_STATUS_SLUG = 'raffle-event-proceed';
    const END_STATUS_SLUG = 'raffle-event-end';
    const NFT_LIST_META_KEY = 'nft_list';
    const START_TIME_KEY = "start_time_int";
    const END_TIME_KEY = "end_time_int";
    const COMLETE_TYPE_KEY = "due_type";
    const GOAL_PARTICIPATE_COUNT_KEY = "full_count";
    
    public function __construct($event_id)
    {
        $this->ID = $event_id;
        $this->post = get_post($this->ID);
        $this->status = $this->post ? "" : false;

        //입력된 아이디에 해당하는 포스트가 없을 때
        if ($this->status === false) {
            return new WP_Error(400, "존재하지 않는 이벤트 아이디입니다.");
        }
    }

    //추첨하는 함수
    static function make_raffle($event_id)
    {
        $nft_ids = RaffleEvent_EventData::get_all_nft_ids($event_id);
        shuffle($nft_ids);
        $result = [];
        foreach ($nft_ids as $value) {
            $post = get_post($value);
            $nft_name = $post->post_title;
            $participants_list = get_post_meta($value, 'participants_list');
            $result[$nft_name] = false;
            while (count($participants_list) > 0) {
                $random_key = array_rand($participants_list, 1);
                $raffle_users = array_values($result);
                $current_raffle_user = $participants_list[$random_key];
                if (in_array($current_raffle_user, $raffle_users)) {
                    unset($participants_list[$random_key]);
                } else {
                    $result[$nft_name] = $participants_list[$random_key];
                    break;
                }
            }
        }
        return $result;
    }
    
    //전체 선착순 수 - 현재 참여자 수를 구하는 함수
    static function get_current_full_count($event_id)
    {
        return (get_post_meta($event_id, "full_count", true) * 1) - self::get_all_participates_count_of_event($event_id);
    }

    //이벤트가 발표되었는지 확인하는 함수
    static function check_event_is_announced($event_id)
    {
        $query = new WP_Query([
            "post_parent" => $event_id,
            "post_type" => "raffle_announce",
            "post_status" => ["draft", "publish"],
            "fields" => "ids"
        ]);
        return $query->found_posts > 0 ? $query->posts[0] : false;
    }

    //이벤트 수정 url을 구하는 함수
    static function get_raffle_event_announce_edit_url($event_id)
    {
        $edit_url_path = admin_url("/post.php?post=%s&action=edit");
        $announce_id = RaffleEvent_EventData::check_event_is_announced($event_id);
        return sprintf($edit_url_path, $announce_id);
    }

    //nft list 문자열에서 nft id를 구하는 함수
    static function get_nft_id_from_nft_list_string($value)
    {
        preg_match("/-[^:\d]*:?(\d+)/", $value, $match);
        $id = @$match[1] ?: false;
        return $id;
    }

    //이벤트에 종속된 모든 nft id들을 구하는 함수 array
    static function get_all_nft_ids($event_id)
    {
        $result = [];
        $event_nft_list = @get_post_meta($event_id, "nft_list", true) ?: [];
        foreach ($event_nft_list as $value) {
            $id = RaffleEvent_EventData::get_nft_id_from_nft_list_string($value);
            array_push($result, $id);
        }
        $event_nft_group_list = RaffleEvent_EventData::get_nft_group_items($event_id);
        //다시 nft_list쿼리
        foreach ($event_nft_group_list as $value) {
            $nft_list = @get_post_meta($value->ID, "nft_list", true) ?: [];
            foreach ($nft_list as $key => $value) {
                $id = RaffleEvent_EventData::get_nft_id_from_nft_list_string($value);
                array_push($result, $id);
            }
        }
        $result = array_filter($result, function ($x) {
            return $x;
        });
        return $result;
    }

    //nft list인자(type-id형 원소)의 모든 참가자 수를 구하는 함수
    static function get_participate_count_nft_items($nft_list)
    {
        $count = 0;
        foreach ($nft_list as $value) {
            preg_match("/-[^:\d]*:?(\d+)/", $value, $match);
            $id = @$match[1] ?: 0;
            $participants_list = get_post_meta($id, "participants_list");
            $count += count($participants_list);
        }
        return $count;
    }

    //이벤트의 모든 참여자 수를 구하는 함수
    static function get_all_participates_count_of_event($event_id)
    {
        $count = 0;
        //nft_list쿼리
        $event_nft_list = @get_post_meta($event_id, "nft_list", true) ?: [];
        $count += RaffleEvent_EventData::get_participate_count_nft_items($event_nft_list);
        //nft_group쿼리
        $event_nft_group_list = RaffleEvent_EventData::get_nft_group_items($event_id);
        //다시 nft_list쿼리
        foreach ($event_nft_group_list as $key => $value) {
            $nft_list = @get_post_meta($value->ID, "nft_list", true) ?: [];
            $count += RaffleEvent_EventData::get_participate_count_nft_items($nft_list);
        }
        return $count;
    }

    //이벤트 내의 한 유저의 모든 참여수를 구하는 함수
    static function get_all_participates_count_of_user_in_event($user_id, $event_id)
    {
        function get_array_count($event_nft_list, $user_id)
        {
            $count = 0;
            foreach ($event_nft_list as $key => $value) {
                preg_match("/-[^:\d]*:?(\d+)/", $value, $match);
                $id = @$match[1] ?: 0;
                $participants_list = get_post_meta($id, "participants_list");
                if (in_array($user_id, $participants_list)) $count++;
            }
            return $count;
        }
        $count = 0;
        //nft_list쿼리
        $event_nft_list = @get_post_meta($event_id, "nft_list", true) ?: [];
        $count += get_array_count($event_nft_list, $user_id);
        //nft_group쿼리
        $event_nft_group_list = RaffleEvent_EventData::get_nft_group_items($event_id);
        //다시 nft_list쿼리
        foreach ($event_nft_group_list as $key => $value) {
            $nft_list = @get_post_meta($value->ID, "nft_list", true) ?: [];
            $count += get_array_count($nft_list, $user_id);
        }
        return $count;
    }

    //이벤트 카테고리의 슬러그를 구하는 함수
    static function get_event_slug($event_id)
    {
        $categories = @wp_get_post_categories($event_id) ?: [];
        return  @$categories[0] ? $categories[0]->slug : "ERROR";
    }

    //이벤트의 링크를 구하는 함수
    static function get_event_link_by_id($event_id)
    {
        $path = "/hwa-sum/raffle-event/detail/" . RaffleEvent_EventData::get_event_slug($event_id) . $event_id;
        return $path;
    }

    //nft item의 아이디로부터 이벤트 아이디를 구하는 함수
    static function get_event_id_from_item_id($item_id)
    {
        $nft_list_regexp = "\"[^-\"]+-[^:\d\"]*:?" . $item_id . "\";";
        $meta_key = "nft_list";
        global $wpdb;
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT post.ID, post.post_type, meta.meta_value
                FROM
                    $wpdb->postmeta AS meta,
                    $wpdb->posts AS post
                WHERE
                    meta.post_id = post.ID AND
                    meta.meta_key = '$meta_key' AND
                    meta.meta_value REGEXP '%s'
                ",
                $nft_list_regexp
            ),
            ARRAY_A
        );
        //메타 밸류로 검색
        if (count($result) > 0) {
            $event_id = @$result[0]["ID"] ?: 0;
            if ($event_id === 0) {
                return new WP_Error(404, "결과가 없습니다.");
            }
            if (get_post_type($event_id) === "raffle_nft_group") {
                $event_id = wp_get_post_parent_id($event_id);
            }
            return $event_id;
        } else {
            return new WP_Error(404, "결과가 없습니다.");
        }
    }

    //이벤트의 모든 nft group을 구하는 함수
    public function get_nft_group_items($event_id = null)
    {
        return get_posts([
            "post_type" => "raffle_nft_group",
            "post_parent" => $event_id ?: $this->ID,
            "nopaging" => true,
        ]);
    }

    //이벤트의 상태(카테고리)를 업데이트하는 함수
    public function update_event_status()
    {
        $event_id = $this->ID;
        $slug = $this->calc_event_status($event_id);
        $this->status = $slug;
        $category = get_category_by_slug($slug);
        if ($category === false) {
            return false;
        }
        $category_id = $category->term_id;
        return wp_update_post([
            "ID" => $event_id,
            "post_category" => [$category_id]
        ], true);
    }

    //이벤트의 현재 상태가 어때야하는지를 계산하는 함수(종료인지, 예정인지.. 시간을 기준으로 판단)
    public function calc_event_status($event_id)
    {
        $UPCOMMING_STATUS_SLUG = $this->UPCOMMING_STATUS_SLUG;
        $PROCEED_STATUS_SLUG = $this->PROCEED_STATUS_SLUG;
        $END_STATUS_SLUG = $this->END_STATUS_SLUG;
        $NFT_LIST_META_KEY = $this->NFT_LIST_META_KEY;
        $START_TIME_KEY = $this->START_TIME_KEY;
        $END_TIME_KEY = $this->END_TIME_KEY;
        $COMLETE_TYPE_KEY = $this->COMLETE_TYPE_KEY;
        $GOAL_PARTICIPATE_COUNT_KEY = $this->GOAL_PARTICIPATE_COUNT_KEY;

        $current_UTC9_time = time();
        $nft_lists = get_post_meta($event_id, $NFT_LIST_META_KEY, true);
        $participants_count = 0;
        $participants_list = [];

        //nft리스트를 참조하여 이벤트에 속한 nft들에 응모한 사람들의 정보를 가져옴
        //선착순 이벤트의 완료 여부를 선착순 카운트와 비교하기 위함
        if ($nft_lists) {
            foreach ($nft_lists as $value) {
                preg_match("/-.*?:?(\d+)/", $value, $match);
                $id = $match[1];
                $nft_item_participants_list = get_post_meta($id, "participants_list");
                $participants_count  += count($nft_item_participants_list);
                $participants_list = array_merge(
                    $participants_list,
                    $nft_item_participants_list
                );
            }
        }
        $start_time = get_post_meta($event_id, $START_TIME_KEY, true);
        $end_tiem = get_post_meta($event_id, $END_TIME_KEY, true);
        $due_type = get_post_meta($event_id, $COMLETE_TYPE_KEY, true);
        @$goal_participate_count = get_post_meta($event_id, $GOAL_PARTICIPATE_COUNT_KEY, true) * 1;

        //예정됨    
        if ($start_time > $current_UTC9_time) {
            $status = $UPCOMMING_STATUS_SLUG;
        }
        switch ($due_type) {
            case "full":
                //종료
                if ($participants_count === $goal_participate_count) {
                    $status = $END_STATUS_SLUG;
                    break;
                }
                //진행중
                if (
                    $start_time < $current_UTC9_time
                ) {
                    $status = $PROCEED_STATUS_SLUG;
                }
                break;
            case "date":
                //진행중
                if (
                    $start_time < $current_UTC9_time
                    && $end_tiem > $current_UTC9_time
                ) {


                    $status = $PROCEED_STATUS_SLUG;
                    break;
                }
                //종료
                if (
                    $end_tiem < $current_UTC9_time
                ) {
                    $status = $END_STATUS_SLUG;
                    break;
                }
                break;
            default:
                break;
        }
        return @$status;
    }
}
