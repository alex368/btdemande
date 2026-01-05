<?php

return [
    [
        "type" => "function",
        "function" => [
            "name" => "send_email",
            "description" => "Envoie un email via Gmail.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "to" => ["type" => "string"],
                    "subject" => ["type" => "string"],
                    "body" => ["type" => "string"]
                ],
                "required" => ["to", "subject", "body"]
            ]
        ]
    ]
];






