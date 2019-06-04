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
        $calendars = \QUI\Calendar\Handler::getCalendars();

        foreach ($calendars as $k => $calendar) {
            // Only return calendars the user can edit
            try {
                $Calendar = Handler::getCalendar($calendar['id']);
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                unset($calendars[$k]);
                continue;
            }
        }

        return $calendars;
    },
    false,
    'Permission::checkAdminUser'
);
