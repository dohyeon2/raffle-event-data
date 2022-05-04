<?php
new RaffleEvent_CustomEndpoint([
    "method" => "POST",
    "path" => "/nft/group",
    "namespace" => "raffle_event/v1",
    "callback" => function ($request) {
        if (current_user_can("administrator")) {
        } else {
            return new WP_REST_Response("잘못된 요청", 400);
        }
    }
]);
