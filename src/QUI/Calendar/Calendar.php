<?php
/**
 * This file contains QUI\Calendar\Calendar
 */
namespace QUI\Calendar;

use Eluceo\iCal\Component\Event;
use QUI;

/**
 * Class Calendar
 * one Calendar
 *
 * @package QUI\Calendar
 */
class Calendar
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var false|null|QUI\Users\Nobody|QUI\Users\SystemUser|QUI\Users\User
     */
    private $user = null;

    /**
     * Calendar constructor. Returns a calendar object for the given calendar id.
     *
     * @param int - $calendarId
     *
     * @throws QUI\Calendar\Exception
     */
    public function __construct($calendarId)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendars(),
            'where' => array(
                'id' => (int)$calendarId
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            throw new QUI\Calendar\Exception(array(
                'quiqqer/calendar',
                'exception.calendar,not.found'
            ));
        }

        $result = $result[0];

        $this->id   = $calendarId;
        $this->name = $result['name'];

        if (!is_null($result['userid'])) {
            $this->user = QUI::getUsers()->get($result['userid']);
        }
    }

    /**
     * Edits the calendars values
     *
     * @param $name - The new calendar name
     * @param null|QUI\Users\User $user - The new calendar owner, null for global calendar
     */
    public function editCalendar($name, $user = null)
    {
        $update = array(
            'name'   => $name,
            'userid' => null
        );

        $this->name = $name;

        if (!empty($user) && !is_null($user)) {
            $update['userid'] = $user->getId();
            $this->user       = $user;
        }

        QUI::getDataBase()->update(
            Handler::tableCalendars(),
            $update,
            array(
                'id' => $this->getId()
            )
        );
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
     * Converts a UNIX timestamp to the format for DHTMLX Scheduler
     *
     * @param $timestamp int - A unix timestamp
     * @return false|string  - The converted timestamp or false on error
     */
    public function timestampToSchedulerFormat($timestamp)
    {
        return date("Y-m-d H:i", $timestamp);
    }


    /**
     * Returns the calendars ID.
     *
     * @return int - the calendar ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the calendars name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the calendars owner User object or null if the calendar is global.
     *
     * @return null|QUI\Users\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns if the calendar is global.
     *
     * @return bool
     */
    public function isGlobal()
    {
        return is_null($this->user);
    }

    /**
     * Returns all events in a calendar as an array
     *
     * @return array - array of events
     */
    public function getEvents()
    {
        return QUI::getDataBase()->fetch(array(
            'from'  => Handler::tableCalendarsEvents(),
            'where' => array(
                'calendarid' => (int)$this->getId()
            )
        ));
    }

    /**
     * Converts the calendars information to an array. Does not include events.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'isGlobal'     => $this->isGlobal(),
            'calendarname' => $this->getName(),
            'id'           => $this->getId()
        );
    }
}
