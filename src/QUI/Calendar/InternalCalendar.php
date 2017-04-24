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
            throw new Exception('Calendar is external not internal');
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
                "'" . $Event->title . "'",
                "'" . $Event->desc . "'",
                $Event->start,
                $Event->end,
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
            $start->setTimestamp($event['start']);

            $end = new \DateTime();
            $end->setTimestamp($event['end']);

            $CalendarEvent = new Event();

            $CalendarEvent->setDtStart($start)
                ->setDtEnd($end)
                ->setSummary($event['title'])
                ->setDescription($event['desc'])
                ->setUniqueId($event['eventid']);

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

        $eventsRaw       = $this->getEvents();
        $eventsFormatted = array();

        foreach ($eventsRaw as $key => $event) {
            $eventsFormatted[$key]['id']          = (int)$event['eventid'];
            $eventsFormatted[$key]['text']        = $event['title'];
            $eventsFormatted[$key]['description'] = $event['desc'];
            $eventsFormatted[$key]['start_date']  = $this->timestampToSchedulerFormat($event['start']);
            $eventsFormatted[$key]['end_date']    = $this->timestampToSchedulerFormat($event['end']);
            $eventsFormatted[$key]['calendar_id'] = $event['calendarid'];
        }

        return json_encode($eventsFormatted);
    }


    /**
     * Returns all events in a calendar as an array
     *
     * @return array - array of events
     */
    public function getEvents()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendarsEvents(),
            'where' => array(
                'calendarid' => (int)$this->getId()
            )
        ));
    }
}
