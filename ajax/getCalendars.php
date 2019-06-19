<?php

/**
 * Return all calendars as an Array.
 *
 * @return array
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendars',
    function () {
        return Handler::getCalendars();
    },
    false
);
