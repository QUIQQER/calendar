<?php

/**
 * This file contains QUI\Calendar\Setup
 */
namespace QUI\Calendar;

use QUI;

/**
 * Setup routine for calendar package
 * @package QUI\Calendar\Setup
 */
class Setup
{
    public static function run()
    {
        $Tables = QUI::getDataBase()->table();

        // Delete 'notime' column in events table
        if ($Tables->existColumnInTable(Calendar::$eventsTable, 'notime')) {
            $Tables->deleteColumn(Calendar::$eventsTable, 'notime');
        }
    }
}