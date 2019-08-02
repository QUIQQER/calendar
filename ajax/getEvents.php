<?php

/**
 * Returns all events as an Array.
 *
 * @return array
 */

use QUI\Calendar\EventManager;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getEvents',
    function () {
        return EventManager::getAllEvents();
    },
    false
);
