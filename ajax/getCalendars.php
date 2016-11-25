<?php

/**
 * Return all calendars as a list.
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendars',
    function () {
        $calendars = \QUI\Calendar\Handler::getCalendars();
        foreach ($calendars as $key => $calendar) {
            if (!is_null($calendar['userid']) && !empty($calendar['userid'])) {
                $calendars[$key]['isglobal'] = false;
            } else {
                $calendars[$key]['isglobal'] = true;
            }
        }
        return $calendars;
    },
    false,
    'Permission::checkAdminUser'
);
