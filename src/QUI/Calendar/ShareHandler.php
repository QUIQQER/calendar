<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar;

use QUI;
use QUI\Calendar\Exception\DatabaseException;
use QUI\Calendar\Exception\NoPermissionException;
use QUI\Calendar\Exception\ShareException;
use QUI\System\Log;
use QUI\Users\User;

/**
 * Class Share
 *
 * @package QUI\Calendar
 */
class ShareHandler
{
    /**
     * Returns a URL which returns the given calendar's events as iCal data.
     * This URL can be used to add a QUIQQER calendar to an external service (e.g. Google Calendar or Thunderbird)
     * By passing a user as an argument the URL for a specific user can be retrieved.
     *
     * @param AbstractCalendar $Calendar - A calendar to get the share URL for
     * @param User             $User     - The user to get the URL for
     *
     * @return string
     *
     * @throws NoPermissionException - The user is not permitted to view the calendar
     * @throws DatabaseException - Couldn't read/write from/to database
     * @throws ShareException - Couldn't generate a share-hash (missing entropy)
     */
    public static function getShareUrlForCalendar(AbstractCalendar $Calendar, User $User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        $Calendar->checkPermission($Calendar::PERMISSION_VIEW_CALENDAR, $User);

        // Check if there is already a share hash for this calendar and user
        try {
            $shareData = QUI::getDataBase()->fetch(
                [
                'from'  => Handler::tableCalendarsShares(),
                'where' => [
                    'calendarid' => (int)$Calendar->getId(),
                    'userid'     => $User->getId()
                ],
                'limit' => 1
                ]
            );
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new DatabaseException();
        }

        if (isset($shareData[0]) && isset($shareData['hash'])) {
            return self::generateShareUrlForHash($shareData['hash']);
        }

        $hash = self::generateShareHash();

        try {
            QUI::getDataBase()->insert(
                Handler::tableCalendarsShares(),
                [
                    'calendarid'   => $Calendar->getId(),
                    'userid'       => $User->getId(),
                    'hash'         => $hash,
                    'date_created' => date("Y-m-d H:i:s")
                ]
            );
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new DatabaseException();
        }

        return self::generateShareUrlForHash($hash);
    }


    /**
     * Generates a new share hash.
     *
     * @throws ShareException - Thrown when it's not possible to generate a share url (missing entropy)
     */
    protected static function generateShareHash()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception $Exception) {
            throw new ShareException();
        }
    }


    /**
     * Returns the share URL for a given hash.
     *
     * @param $hash
     *
     * @return string
     */
    protected static function generateShareUrlForHash($hash)
    {
        $host  = QUI::conf('globals', 'host') . "/";
        $path  = "packages/quiqqer/calendar/bin/iCalExport.php";
        $query = "?hash=" . $hash;

        return $host . $path . $query;
    }


    /**
     * Returns a calendar from a given share URL hash.
     *
     * @param string $hash - The calendars share hash
     *
     * @return AbstractCalendar
     * @throws Exception - No calendar for this hash in the database
     *
     * @throws DatabaseException - Couldn't fetch the calendar data from the database
     */
    public static function getCalendarFromHash($hash)
    {
        try {
            $calendarData = QUI::getDataBase()->fetch(
                [
                'select' => ['calendarid'],
                'from'   => Handler::tableCalendarsShares(),
                'where'  => [
                    'hash' => $hash
                ],
                'limit'  => 1
                ]
            );
        } catch (\QUI\Exception $Exception) {
            Log::writeException($Exception);
            throw new DatabaseException();
        }

        if (!isset($calendarData[0])) {
            throw new Exception(['quiqqer/calendar', 'exception.calendar.not_found']);
        }

        return Handler::getCalendar($calendarData[0]['calendarid']);
    }
}
