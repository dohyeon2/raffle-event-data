<?php
new raffle_event_custom_post([
    "register_options" => [
        "show_in_menu" => "raffle",
        "taxonomies" => ["category"],
        "supports" => [
            "title",
            "editor",
            "thumbnail",
            "custom-fields"
        ]
    ],
    "post_type_name" => "nft_data",
    "post_label_name" => "NFT 데이터 포스트"
], [], []);