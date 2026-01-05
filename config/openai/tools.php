<?php

return [
    [
        "type" => "function",
        "function" => [
            "name" => "create_event",
            "description" => "Créer un rendez-vous dans Google Calendar.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "title" => ["type" => "string"],
                    "start" => ["type" => "string", "format" => "date-time"],
                    "end"   => ["type" => "string", "format" => "date-time"]
                ],
                "required" => ["title", "start", "end"]
            ]
        ]
    ]
];
