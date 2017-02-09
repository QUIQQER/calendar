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
        foreach ($calendars as $key => $Calendar) {
            if (!is_null($Calendar['userid']) && !empty($Calendar['userid'])) {
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
