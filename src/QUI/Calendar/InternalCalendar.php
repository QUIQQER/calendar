<?php
/**
 * This file contains QUI\Calendar\Calendar
 */

namespace QUI\Calendar;

use function DusanKasan\Knapsack\last;
use Eluceo\iCal\Component\Event;
use QUI;

/**
 * Class Calendar
 * one Calendar
 *
 * @package QUI\Calendar
 */
class InternalCalendar extends AbstractCalendar
{
    /**
     * @inheritdoc
     */
    protected function construct($data)
    {
        if ($data['isExternal'] == 1) {
            throw new Exception("Calendar with ID {$this->getId()} is external but was created as internal");
        }

        parent::construct($data);
    }

    /**
     * Adds an event to the calendar.
     *
     * @param string $title - Event title
     * @param string $desc - Event description
     * @param int $start - Unix timestamp when the event starts
     * @param int $end - Unix timestamp when the event ends
     *
     * @return int - The ID the event got assigned from the database
     *
     */
    public function addCalendarEvent($title, $desc, $start, $end)
    {
        $this->checkPermission(self::PERMISSION_ADD_EVENT);

        QUI::getDataBase()->insert(Handler::tableCalendarsEvents(), array(
            'title'      => $title,
            'desc'       => $desc,
            'start'      => $start,
            'end'        => $end,
            'calendarid' => $this->getId()
        ));

        return QUI::getDataBase()->getPDO()->lastInsertId('eventid');
    }


    /**
     * Adds multiple events at once to the calendar.
     *
     * @param \QUI\Calendar\Event[] $events
     */
    public function addCalendarEvents($events)
    {
        if (!is_array($events) || empty($events)) {
            return;
        }

        $sql      = "INSERT INTO " . Handler::tableCalendarsEvents() . " (title, `desc`, start, `end`, calendarid) VALUES ";
        $lastElem = last($events);
        foreach ($events as $Event) {
            $data = implode(',', [
                "'" . $Event->text . "'",
                "'" . $Event->description . "'",
                $Event->start_date,
                $Event->end_date,
                $this->getId()
            ]);
            $sql  = $sql . "($data)";
            if ($Event != $lastElem) {
                $sql = $sql . ",";
            }
        }

        $sql = $sql . ";";

        QUI::getDataBase()->getPDO()->prepare($sql)->execute();
    }

    /**
     * Edits an event in the calendar.
     *
     * @param int $eventID - ID of the event to edit
     * @param string $title - Event title
     * @param string $desc - Event description
     * @param int $start - Unix timestamp when the event starts
     * @param int $end - Unix timestamp when the event ends
     */
    public function editCalendarEvent($eventID, $title, $desc, $start, $end)
    {
        $this->checkPermission(self::PERMISSION_EDIT_EVENT);

        QUI::getDataBase()->update(Handler::tableCalendarsEvents(), array(
            'title' => $title,
            'desc'  => $desc,
            'start' => $start,
            'end'   => $end
        ), array(
            'eventid' => $eventID
        ));
    }

    /**
     * Removes an event from the calendar.
     *
     * @param int $eventID - ID of the event to remove
     */
    public function removeCalendarEvent($eventID)
    {
        $this->checkPermission(self::PERMISSION_REMOVE_EVENT);

        QUI::getDataBase()->delete(Handler::tableCalendarsEvents(), array(
            'eventid' => $eventID
        ));
    }

    /**
     * Converts a calendar and all its' events to iCal format
     *
     * @return string - The calendar in iCal format
     */
    public function toICal()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $Calendar = new \Eluceo\iCal\Component\Calendar($this->getId());
        $Events   = $this->getEvents();

        foreach ($Events as $event) {
            $start = new \DateTime();
            $start->setTimestamp($event->start_date);

            $end = new \DateTime();
            $end->setTimestamp($event->end_date);

            $CalendarEvent = new Event();

            $CalendarEvent->setDtStart($start)
                ->setDtEnd($end)
                ->setSummary($event->text)
                ->setDescription($event->description)
                ->setUniqueId($event->id);

            $Calendar->addComponent($CalendarEvent);
        }

        return $Calendar->render();
    }


    /**
     * Converts the calendars events to JSON format
     *
     * @return string - The calendars events in JSON format
     */
    public function toJSON()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $events = $this->getEvents();

        foreach ($events as $Event) {
            $Event->start_date = $this->timestampToSchedulerFormat($Event->start_date);
            $Event->end_date   = $this->timestampToSchedulerFormat($Event->end_date);
        }

        return json_encode($events);
    }


    /**
     * Returns all events in a calendar as an array
     *
     * @return \QUI\Calendar\Event[] - array of events
     */
    public function getEvents()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $eventsRaw = QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendarsEvents(),
            'where' => array(
                'calendarid' => (int)$this->getId()
            )
        ));

        $events = array();
        foreach ($eventsRaw as $event) {
            $events[] = \QUI\Calendar\Event::fromDatabaseArray($event);
        }

        return $events;
    }


    /**
     * @inheritdoc
     */
    public function getUpcomingEvents($amount = -1)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $table = Handler::tableCalendarsEvents();

        $sql = "
            SELECT * 
            FROM {$table}
            WHERE 
              calendarid = :calendarID AND 
              start >= :startDate
            ORDER BY start ASC
        ";

        $parameters = array(
            ':calendarID' => (int)$this->getId(),
            ':startDate'  => time()
        );

        if (is_numeric($amount) && $amount > -1) {
            $limit = (int)$amount;
            $sql   .= "LIMIT {$limit}";
        }

        $Statement = QUI::getDataBase()->getPDO()->prepare($sql);
        $Statement->execute($parameters);

        $eventsRaw = $Statement->fetchAll(\PDO::FETCH_ASSOC);

        $events = array();
        foreach ($eventsRaw as $event) {
            $events[] = \QUI\Calendar\Event::fromDatabaseArray($event);
        }

        return $events;
    }
}
