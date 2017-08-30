<?php

/**
 * This file contains QUI\Calendar\Handler
 */

namespace QUI\Calendar;

use ICal\ICal;
use QUI;
use QUI\Users\User;

/**
 * Class Handler
 * @package QUI\Calendar
 */
class Handler
{
    /**
     * Creates a new Calendar
     *
     * @param string $name - Calendar name
     * @param User $User - Owner of the calendar
     * @param bool $isPublic - Is the calendar private or public?
     * @param string $color - The calendars color in hex format (leading #)
     *
     * @return InternalCalendar - The created calendar
     */
    public static function createCalendar($name, $User, $isPublic = false, $color = '#2F8FC6')
    {
        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'     => $name,
            'userid'   => $User->getId(),
            'isPublic' => $isPublic ? 1 : 0,
            'color'    => $color
        ));
        $calendarID = QUI::getPDO()->lastInsertId();

        return new InternalCalendar($calendarID);
    }

    /**
     * Creates a new calendar (with events) from an iCal URL
     *
     * @param string $icalUrl - The iCal URL
     * @param User $User - The calendar owner
     *
     * @return InternalCalendar - The created calendar object
     */
    public static function createCalendarFromIcal($icalUrl, $User)
    {
        // Translation of the word "Calendar"
        $calendarTranslation = QUI::getLocale()->get('quiqqer/calendar', 'calendar');

        $IcalCalendar = new ICal($icalUrl);
        $Calendar     = self::createCalendar($User->getName() . " " . $calendarTranslation, $User);

        $eventsFromIcal = $IcalCalendar->events();

        $events = array();
        foreach ($eventsFromIcal as $IcalEvent) {
            $events[] = new Event(
                $IcalEvent->summary,
                $IcalEvent->description,
                (int)$IcalCalendar->iCalDateToUnixTimestamp($IcalEvent->dtstart),
                (int)$IcalCalendar->iCalDateToUnixTimestamp($IcalEvent->dtend)
            );
        }

        $Calendar->addCalendarEvents($events);

        return $Calendar;
    }


    /**
     * Adds an external calendar to the system.
     *
     * @param string $calendarName - Name of the calendar
     * @param string $icalUrl - URL of the iCal (.ics) file
     * @param User $User - Owner of the calendar
     * @param bool $isPublic - Is the calendar private or public?
     * @param string $color - The calendars color in hex format (leading #)
     *
     * @return ExternalCalendar
     * @throws Exception
     */
    public static function addExternalCalendar(
        $calendarName,
        $icalUrl,
        $User = null,
        $isPublic = false,
        $color = '#2F8FC6'
    ) {
        if (!ExternalCalendar::isUrlReachable($icalUrl)) {
            $msg = QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.external.error.url.invalid'
            );
            throw new Exception($msg);
        }

        // TODO: Issue #21
        if (!ExternalCalendar::isValidIcal(file_get_contents($icalUrl))) {
            $msg = QUI::getLocale()->get(
                'quiqqer/calendar',
                'exception.ical.invalid'
            );
            throw new Exception($msg);
        }

        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'        => $calendarName,
            'userid'      => $User->getId(),
            'isPublic'    => $isPublic,
            'isExternal'  => 1,
            'externalUrl' => $icalUrl,
            'color'       => $color
        ));
        $calendarID = QUI::getPDO()->lastInsertId();

        return new ExternalCalendar($calendarID);
    }


    public static function isExternalCalendar($calendarID)
    {
        $isExternal = QUI::getDataBase()->fetch(array(
            'select' => 'isExternal',
            'from'   => self::tableCalendars(),
            'where'  => array(
                'id' => $calendarID
            ),
            'limit'  => 1
        ));

        if (empty($isExternal)) {
            throw new Exception('Calendar does not exist.');
        }

        return $isExternal[0]['isExternal'] == 1 ? true : false;
    }


    /**
     * The name of the database table containing calendars
     *
     * @return string
     */
    public static function tableCalendars()
    {
        return QUI::getDBTableName('calendars');
    }

    /**
     * The name of the database table containing calendar events
     *
     * @return string
     */
    public static function tableCalendarsEvents()
    {
        return QUI::getDBTableName('calendars_events');
    }

    /**
     * Deletes calendars with the given IDs from the database
     *
     * @param array $ids - The IDs of calendar which should be deleted
     */
    public static function deleteCalendars($ids)
    {
        $Database = QUI::getDataBase();

        foreach ($ids as $id) {
            $id = (int)$id;

            if (self::isExternalCalendar($id)) {
                $Calendar = new ExternalCalendar($id);
            } else {
                $Calendar = new InternalCalendar($id);
            }

            $Calendar->checkPermission($Calendar::PERMISSION_DELETE_CALENDAR);

            $Database->delete(self::tableCalendars(), array(
                'id' => $id
            ));

            $Database->delete(self::tableCalendarsEvents(), array(
                'calendarid' => $id
            ));
        }
    }

    /**
     * Returns all calendars from database.
     *
     * @return array - all calendars in the database
     */
    public static function getCalendars()
    {
        $calendars = QUI::getDataBase()->fetch(array(
            'from' => self::tableCalendars()
        ));

        foreach ($calendars as $key => $calendarData) {
            if ($calendarData['isExternal'] == 1) {
                $Calendar = new ExternalCalendar($calendarData['id']);
            } else {
                $Calendar = new InternalCalendar($calendarData['id']);
            }

            // Only return calendars the user can edit
            try {
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                unset($calendars[$key]);
                continue;
            }

            $calendars[$key]['isPublic']   = $calendarData['isPublic'] == 1 ? true : false;
            $calendars[$key]['isExternal'] = $calendarData['isExternal'] == 1 ? true : false;
        }

        // Return array with new indexes starting at 0
        return array_values($calendars);
    }


    /**
     * Returns all external calendars from database.
     *
     * @return ExternalCalendar[] - All external calendars in the database
     */
    public static function getExternalCalendars()
    {
        $calendarsRaw = QUI::getDataBase()->fetch(array(
            'from'  => self::tableCalendars(),
            'where' => array(
                'isExternal' => 1
            )
        ));

        $calendars = array();
        foreach ($calendarsRaw as $calendarData) {
            $Calendar = new ExternalCalendar($calendarData['id']);

            // Only return calendars the user can edit
            try {
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                continue;
            }

            $calendars[] = $Calendar;
        }

        // Return array with new indexes starting at 0
        return $calendars;
    }


    /**
     * Returns the calendar with the given ID
     *
     * @param int $calendarID
     *
     * @return ExternalCalendar|InternalCalendar - internal or external calendar
     *
     * @throws Exception - If calendar not found
     */
    public static function getCalendar($calendarID)
    {
        $calendarRaw = QUI::getDataBase()->fetch(array(
            'from'  => self::tableCalendars(),
            'where' => array(
                'id' => $calendarID
            ),
            'limit' => 1
        ));

        if (!isset($calendarRaw[0])) {
            throw new QUI\Calendar\Exception(array(
                'quiqqer/calendar',
                'exception.calendar.not_found'
            ));
        }

        $calendarRaw = $calendarRaw[0];

        if ($calendarRaw['isExternal'] == 1) {
            $Calendar = new ExternalCalendar($calendarID);
        } else {
            $Calendar = new InternalCalendar($calendarID);
        }

        return $Calendar;
    }
}
