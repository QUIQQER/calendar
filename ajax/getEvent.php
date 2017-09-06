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
        $Event = \QUI\Calendar\EventManager::getEventById($eventID);

        if (!is_null($Event)) {
            return $Event->toArray();
        }

        return null;
    },
    array('eventID'),
    'Permission::checkAdminUser'
);
