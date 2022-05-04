<?php

class RaffleEvent_EventData
{
    public function __construct($event_id)
    {
        $this->UPCOMMING_STATUS_SLUG = 'raffle-event-upcomming';
        $this->PROCEED_STATUS_SLUG = 'raffle-event-proceed';
        $this->END_STATUS_SLUG = 'raffle-event-end';
        $this->NFT_LIST_META_KEY = 'nft_list';
        $this->START_TIME_KEY = "start_time_int";
        $this->END_TIME_KEY = "end_time_int";
        $this->COMLETE_TYPE_KEY = "due_type";
        $this->GOAL_PARTICIPATE_COUNT_KEY = "full_count";
        $this->ID = $event_id;
        $this->post = get_post($this->ID);
        $this->status = $this->post ? "" : false;

        //입력된 아이디에 해당하는 포스트가 없을 때
        if ($this->status === false) {
            return new WP_Error(400, "존재하지 않는 이벤트 아이디입니다.");
        }

        return $this;
    }
    public function get_nft_group_items()
    {
        return get_posts([
            "post_type" => "raffle_nft_group",
            "post_parent" => $this->ID,
            "nopaging" => true,
        ]);
    }

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

        $current_UTC9_time = time() + (HOUR_IN_SECONDS * 9);
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
