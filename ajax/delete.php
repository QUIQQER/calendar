<?php

/**
 * Delete calendars with the given IDs
 *
 * @param string $ids - JSON array of event IDs
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_delete',
    function ($ids) {
        $ids     = json_decode($ids, true);
        \QUI\Calendar\Handler::deleteCalendars($ids);
    },
    array('ids'),
    'quiqqer.calendar.delete'
);
