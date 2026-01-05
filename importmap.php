<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */


return [
    // ===== ENTRYPOINTS =====
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],

    // ===== FullCalendar =====
    '@fullcalendar/core' => [
        'path' => 'node_modules/@fullcalendar/core/index.js',
    ],

    '@fullcalendar/daygrid' => [
        'path' => 'node_modules/@fullcalendar/daygrid/index.js',
    ],

    '@fullcalendar/icalendar' => [
        'path' => 'node_modules/@fullcalendar/icalendar/index.js',
    ],

    'ical.js' => [
        'path' => 'node_modules/ical.js/build/ical.es.js',
    ],

    // ===== Stimulus =====
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
];
