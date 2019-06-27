<?php

/**
 * This file contains \QUI\Calendar\Cron
 */

namespace QUI\Calendar;

use QUI;
use QUI\Cron\Manager;

/**
 * Class Cron / Calendar Crons
 *
 * @package quiqqer/log
 * @author  www.pcsg.de (Jan Wennrich)
 */
class Cron
{
    /**
     * Syncs all external calendars from their origin with the QUIQQER system
     *
     * @param array             $params
     * @param Manager $CronManager
     */
    public static function syncExternalCalendars($params, $CronManager)
    {
        foreach (Handler::getExternalCalendars() as $Calendar) {
            // Loads non cached calendars into cache
            try {
                $Calendar->toICal();
            } catch (\QUI\Exception $exception) {
                continue;
            }
        }
    }
}
