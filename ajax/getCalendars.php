<?php

/**
 * Return all calendars as an Array.
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendars',
    function () {
        return \QUI\Calendar\Handler::getCalendars();
    },
    false,
    'Permission::checkAdminUser'
);
