<?php

namespace QUI\Calendar\Event;

use QUI;
use QUI\Calendar\Exception\DatabaseException;
use QUI\Calendar\Exception\NoPermissionException;
use QUI\System\Log;

class EventManager
{
    /**
     * Returns the event for a given ID or null if not found
     *
     * @param int $id - An event id
     *
     * @return null|QUI\Calendar\Event The event or null if event not found or no permission to view the events calendar
     *
     * @throws DatabaseException - Couldn't fetch event's data from the database
     * @throws NoPermissionException - No permission to view the calendar's events
     * @throws \Exception - Event couldn't be created from database data
     */
    public static function getEventById($id)
    {
        $tableEvents         = QUI\Calendar\Handler::tableCalendarsEvents();
        $tableRecurrenceData = QUI\Calendar\Handler::tableCalendarsEventsRecurrence();

        // The long SELECT is required, since the duplicate column name 'eventid' in the two table causes null values, if no recurrence data is available
        $query = " 
                SELECT events.`eventid`, `title`, `desc`, `start`, `end`, `calendarid`, `url`, `recurrence_interval`, `recurrence_end`
                FROM `{$tableEvents}` AS events
                    LEFT OUTER JOIN `{$tableRecurrenceData}` AS recurrence_data
                        ON events.`eventid` = recurrence_data.`eventid`
                WHERE events.`eventid` = {$id}
                LIMIT 1;
         ";

        try {
            $data = QUI::getDataBase()->fetchSQL($query);
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new DatabaseException();
        }

        if (empty($data)) {
            return null;
        }

        $Event = EventUtils::createEventFromDatabaseArray($data[0]);

        $Calendar = QUI\Calendar\Handler::getCalendar($Event->getCalendarId());
        $Calendar->checkPermission($Calendar::PERMISSION_VIEW_CALENDAR);

        return $Event;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param int[] $ids   - Array of calendar IDs of which upcoming events should be retrieved
     * @param int   $limit - Maximum amount of events to get
     *
     * @return EventCollection - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendarIds(array $ids, int $limit = 1000)
    {
        $Events = new EventCollection();
        foreach ($ids as $calendarID) {
            try {
                $Events->merge(QUI\Calendar\Handler::getCalendar($calendarID)->getUpcomingEvents($limit));
            } catch (\QUI\Exception $exception) {
                continue;
            }
        }

        $Events->sortByStartDate();

        if ($limit > 0) {
            $eventsCounter = 0;
            $Events->filter(function ($Event) use ($eventsCounter, $limit) {
                $limit++;

                return $eventsCounter > $limit;
            });
        }

        return $Events;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param QUI\Calendar\AbstractCalendar[] $calendars - Array of calendars of which upcoming events should be retrieved
     * @param int                             $limit     - Maximum amount of events to get
     *
     * @return EventCollection - Upcoming events collection
     */
    public static function getUpcomingEventsForCalendars(array $calendars, int $limit = 1000)
    {
        $ids = [];
        foreach ($calendars as $Calendar) {
            $ids[] = $Calendar->getId();
        }

        return self::getUpcomingEventsForCalendarIds($ids, $limit);
    }
}
