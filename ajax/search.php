<?php

/**
 * This file contains package_quiqqer_calendar_ajax_search
 */

use QUI\Calendar\Handler;

/**
 * Returns area list
 *
 * @param string $freeText - Freetext search, String to search
 * @param string $params   - JSON query params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_search',
    function ($freeText) {

        $PDO = QUI::getPDO();

        $freeText          = "%$freeText%";
        $calendarTableName = Handler::tableCalendars();

        $statement = $PDO->prepare("
          SELECT `id`, `name` AS `title`, `isExternal`, 'fa fa-calendar' AS `icon` 
          FROM {$calendarTableName}
          WHERE `name` LIKE :freeText
        ");
        $statement->bindParam(':freeText', $freeText);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    },
    ['freeText'],
    'Permission::checkUser'
);
