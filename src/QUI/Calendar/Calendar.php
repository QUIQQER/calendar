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
     * The calendar ID
     *
     * @var integer
     */
    private $id;

    /**
     * The name of the calendar
     *
     * @var string
     */
    private $name;

    /**
     * The owner of the calendar
     *
     * @var QUI\Users\User
     */
    private $User;


    /**
     * Is the calendar public or private?
     *
     * @var boolean
     */
    private $isPublic;

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
                'exception.calendar.not_found'
            ));
        }

        $result = $result[0];

        $this->id   = $calendarId;
        $this->name = $result['name'];
        $this->User = QUI::getUsers()->get($result['userid']);
        $this->isPublic = $result['isPublic'] == 1 ? true : false;
    }

    /**
     * Edits the calendars values
     *
     * @param $name - The new calendar name
     * @param $isPublic - Is the calendar public?
     */
    public function editCalendar($name, $isPublic)
    {
        $this->checkPermission(self::PERMISSION_EDIT_CALENDAR);

        $this->name     = $name;
        $this->isPublic = $isPublic;

        QUI::getDataBase()->update(
            Handler::tableCalendars(),
            [
                'name'     => $name,
                'isPublic' => $isPublic
            ],
            ['id' => $this->getId()]
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
     * Returns the calendars owner User object.
     *
     * @return QUI\Users\User
     */
    public function getUser()
    {
        return $this->User;
    }

    /**
     * Returns if the calendar is public or private.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->isPublic();
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
            'isPublic'     => $this->isPublic(),
            'calendarname' => $this->getName(),
            'id'           => $this->getId()
        );
    }


    /**
     * Checks if the user can perform a specified action on the calendar
     *
     * @param $permission - Name of the permission to check
     *
     * @return boolean
     *
     * @throws \QUI\Permissions\Exception
     */
    public function checkPermission($permission)
    {
        $User = QUI::getUsers()->getUserBySession();

        if ($User->isSU()) {
            return true;
        }

        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }

        $NoPermissionException = new QUI\Permissions\Exception(
            QUI::getLocale()->get('quiqqer/system', 'exception.no.permission'),
            403
        );

        switch ($permission) {
            case self::PERMISSION_EDIT_CALENDAR:
            case self::PERMISSION_DELETE_CALENDAR:
            case self::PERMISSION_ADD_EVENT:
            case self::PERMISSION_REMOVE_EVENT:
            case self::PERMISSION_EDIT_EVENT:
            default:
                if ($this->isOwner($User)) {
                    return true;
                }
                throw $NoPermissionException;
        }
    }

    /**
     * Checks if the specified user is the owner of this calendar
     *
     * @param QUI\Users\User|QUI\Interfaces\Users\User $User - The user to check
     *
     * @return boolean
     */
    public function isOwner($User)
    {
        return $User->getId() === $this->User->getId();
    }


    const PERMISSION_EDIT_CALENDAR = 'editCalendar';
    const PERMISSION_DELETE_CALENDAR = 'deleteCalendar';
    const PERMISSION_ADD_EVENT = 'addEvent';
    const PERMISSION_REMOVE_EVENT = 'removeEvent';
    const PERMISSION_EDIT_EVENT = 'editEvent';
}
