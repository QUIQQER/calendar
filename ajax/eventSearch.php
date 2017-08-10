<?php

/**
 * This file contains package_quiqqer_calendar_ajax_eventSearch
 */

/**
 * Returns event list
 *
 * @param string $freeText - Freetext search, String to search
 * @param string $params - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_eventSearch',
    function ($freeText) {

        $PDO = QUI::getPDO();

        $freeText       = "%$freeText%";
        $eventTableName = \QUI\Calendar\Handler::tableCalendarsEvents();

        $statement = $PDO->prepare("
          SELECT *
          FROM {$eventTableName}
          WHERE `title` LIKE :freeText
        ");
        $statement->bindParam(':freeText', $freeText);

        $statement->execute();

        $eventsDataRaw = $statement->fetchAll(PDO::FETCH_ASSOC);

        $events = array();
        foreach ($eventsDataRaw as $eventData) {
            $Event = \QUI\Calendar\Event::fromDatabaseArray($eventData);

            $eventAsArray = $Event->toArray();

            $eventAsArray['title'] = $Event->text;

            $events[] = $eventAsArray;
        }

        return array_values($events);
    },
    array('freeText'),
    'Permission::checkAdminUser'
);
