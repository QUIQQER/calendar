<?php

/**
 * This file contains QUI\Calendar\Setup
 */

namespace QUI\Calendar;

use QUI;

/**
 * Setup routine for calendar package
 *
 * @package QUI\Calendar\Setup
 */
class Setup
{
    /**
     * Only for udpates: Delete columns created in older versions of the package
     *
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't delete calendar's without owner/userid from the database
     */
    public static function run()
    {
        $DB     = QUI::getDataBase();
        $Tables = $DB->table();

        $tableCalendarsName = Handler::tableCalendars();
        $tableEventsName    = Handler::tableCalendarsEvents();

        // Delete 'notime' column in events table
        if ($Tables->existColumnInTable($tableEventsName, 'notime')) {
            $Tables->deleteColumn($tableEventsName, 'notime');
        }

        // Delete calendars without owner/userid
        try {
            $DB->delete($tableCalendarsName, ['userid' => null]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\DatabaseException();
        }

        // Delete Events without associated Calendar
        $DB->getPDO()->query(
            "DELETE FROM `$tableEventsName`
             WHERE calendarid NOT IN (
                 SELECT id FROM `$tableCalendarsName`
             )"
        );

        // User ID of calendar owner can no longer be null
        $DB->getPDO()->query(
            "ALTER TABLE `$tableCalendarsName`
             MODIFY userid INT(11) NOT NULL;
            "
        );
    }
}
