<?php

/**
 * Returns event information as an array
 *
 * @param integer $eventID - The ID of the event of which the information should be returned
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getEvent',
    function ($eventID) {
        return \QUI\Calendar\EventManager::getEventById($eventID)->toArray();
    },
    array('eventID'),
    'Permission::checkAdminUser'
);
