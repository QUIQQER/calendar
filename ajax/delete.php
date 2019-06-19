<?php

/**
 * Delete calendars with the given IDs
 *
 * @param string $ids - JSON array of event IDs
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_delete',
    function ($ids) {
        $ids = json_decode($ids, true);
        Handler::deleteCalendars($ids);
    },
    ['ids'],
    'Permission::checkUser'
);
