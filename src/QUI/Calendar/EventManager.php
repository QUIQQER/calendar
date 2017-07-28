<?php

namespace QUI\Calendar;

class EventManager
{
    /**
     * Returns the event for a given ID or null if not found
     *
     * @param int $id - An event id
     * @return null|Event The event or null
     */
    public static function getEventById($id)
    {
        $data = \QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendarsEvents(),
            'where' => array(
                'eventid' => $id
            ),
            'limit' => 1
        ));

        if (is_null($data)) {
            return null;
        }

        $Event = Event::fromDatabaseArray($data);

        return $Event;
    }


    /**
     * Returns the specified amount of events for a calendar id
     *
     * @param int $id - ID of a calendar of which upcoming events should be retrieved
     * @param bool $limit - Maximum amount of events to get
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendarId($id, $limit = false)
    {
        $eventsRaw = \QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendarsEvents(),
            'where' => array(
                'calendarid' => $id,
                'start'      => array(
                    'value' => time(),
                    'type'  => '>='
                )
            ),
            'limit' => $limit,
            'order' => 'start'
        ));

        $events = array();
        foreach ($eventsRaw as $eventData) {
            $events[] = Event::fromDatabaseArray($eventData);
        }

        return $events;
    }


    /**
     * Returns the specified amount of events for a calendar
     *
     * @param AbstractCalendar $Calendar - Calendar of which upcoming events should be retrieved
     * @param bool $limit - Maximum amount of events to get
     * @return Event[] - An array of upcoming events
     */
    public function getUpcomingEventsForCalendar(AbstractCalendar $Calendar, $limit = false)
    {
        return $this->getUpcomingEventsForCalendarId($Calendar->getId(), $limit);
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param int[] $ids - Array of calendar IDs of which upcoming events should be retrieved
     * @param int|bool $limit - Maximum amount of events to get
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendarIds(array $ids, $limit = false)
    {
        $table = Handler::tableCalendarsEvents();

        $ids = implode(', ', $ids);

        $sql = "
            SELECT * 
            FROM {$table}
            WHERE 
              calendarid IN ({$ids}) AND 
              start >= :startDate
            ORDER BY start ASC
        ";

        $parameters = array(
            ':startDate' => time()
        );

        if (is_numeric($limit) && $limit > 0) {
            $limit = (int)$limit;
            $sql   .= "LIMIT {$limit}";
        }

        $Statement = \QUI::getDataBase()->getPDO()->prepare($sql);
        $Statement->execute($parameters);

        $eventsRaw = $Statement->fetchAll(\PDO::FETCH_ASSOC);

        $events = array();
        foreach ($eventsRaw as $eventData) {
            $events[] = Event::fromDatabaseArray($eventData);
        }

        return $events;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param AbstractCalendar[] $calendars - Array of calendars of which upcoming events should be retrieved
     * @param int|bool $limit - Maximum amount of events to get
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendars(array $calendars, $limit = false)
    {
        $ids = array();
        foreach ($calendars as $Calendar) {
            $ids[] = $Calendar->getId();
        }

        return self::getUpcomingEventsForCalendarIds($ids);
    }
}
