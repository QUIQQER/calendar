<?php

/**
 * Returns all events as an Array.
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getEvents',
    function () {
        return \QUI\Calendar\EventManager::getAllEvents();
    },
    false,
    'Permission::checkAdminUser'
);
