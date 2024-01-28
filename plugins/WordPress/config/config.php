<?php

return [
    'observers.global' => DI\add([
        ['Platform.initialized', DI\value(function () {
            if (defined('MATOMO_LOCAL_ENVIRONMENT')
                && MATOMO_LOCAL_ENVIRONMENT
                && !empty($_GET['force-past-date'])
            ) {
                \Piwik\Date::$now = strtotime('2023-01-15 12:00:00');
            }
        })],
    ]),
];
