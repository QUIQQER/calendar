<?php

namespace QUI\Calendar;

use QUI;
use QUI\Users\User;

abstract class AbstractCalendar
{
    /**
     * The calendar ID
     *
     * @var integer
     */
    protected $id;

    /**
     * The name of the calendar
     *
     * @var string
     */
    protected $name;

    /**
     * The owner of the calendar
     *
     * @var User
     */
    protected $User;


    /**
     * Is the calendar public or private?
     *
     * @var boolean
     */
    protected $isPublic;

    /**
     * Is the calendar internal or external?
     *
     * @var boolean
     */
    protected $isExternal;

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

        $this->id         = (int)$calendarId;
        $this->name       = $result['name'];
        $this->User       = QUI::getUsers()->get($result['userid']);
        $this->isPublic   = $result['isPublic'] == 1 ? true : false;
        $this->isExternal = $result['isExternal'] == 1 ? true : false;
    }

    /**
     * Edits the calendars values
     *
     * @param $name - The new calendar name
     * @param $isPublic - Is the calendar public?
     */
    public function editCalendar($name, $isPublic)
    {
        $this->checkPermission(AbstractCalendar::PERMISSION_EDIT_CALENDAR);

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
    abstract public function addCalendarEvent($title, $desc, $start, $end);


    /**
     * Adds multiple events at once to the calendar.
     *
     * @param \QUI\Calendar\Event[] $events
     */
    abstract public function addCalendarEvents($events);

    /**
     * Edits an event in the calendar.
     *
     * @param int $eventID - ID of the event to edit
     * @param string $title - Event title
     * @param string $desc - Event description
     * @param int $start - Unix timestamp when the event starts
     * @param int $end - Unix timestamp when the event ends
     */
    abstract public function editCalendarEvent($eventID, $title, $desc, $start, $end);

    /**
     * Removes an event from the calendar.
     *
     * @param int $eventID - ID of the event to remove
     */
    abstract public function removeCalendarEvent($eventID);

    /**
     * Converts a calendar and all its' events to iCal format
     *
     * @return string - The calendar in iCal format
     */
    abstract public function toICal();


    /**
     * Converts the calendars events to JSON format
     *
     * @return string - The calendars events in JSON format
     */
    abstract public function toJSON();


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
     * @return User
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
        return $this->isPublic;
    }

    /**
     * Returns if the calendar is internal or external.
     *
     * @return bool
     */
    public function isExternal()
    {
        return $this->isExternal;
    }

    /**
     * Returns all events in a calendar as an array
     *
     * @return array - array of events
     */
    abstract public function getEvents();

    /**
     * Converts the calendars information to an array. Does not include events.
     *
     * @return array
     */
    abstract public function toArray();


    /**
     * Checks if the user can perform a specified action on the calendar
     *
     * @param $permission - Name of the permission to check
     *
     * @return boolean
     *
     * @throws \QUI\Calendar\Exception
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

        switch ($permission) {
            case self::PERMISSION_VIEW_CALENDAR:
                if ($this->isOwner($User) || $this->isPublic()) {
                    return true;
                } else {
                    throw new Exception(array(
                        'quiqqer/calendar',
                        'exception.calendar.permission.view'
                    ));
                }
                break;
            case self::PERMISSION_EDIT_CALENDAR:
            case self::PERMISSION_DELETE_CALENDAR:
            case self::PERMISSION_ADD_EVENT:
            case self::PERMISSION_REMOVE_EVENT:
            case self::PERMISSION_EDIT_EVENT:
            default:
                if ($this->isOwner($User)) {
                    return true;
                }
                throw new Exception(array(
                    'quiqqer/calendar',
                    'exception.calendar.permission.edit'
                ));
        }
    }

    /**
     * Checks if the specified user is the owner of this calendar
     *
     * @param User|\QUI\Interfaces\Users\User $User - The user to check
     *
     * @return boolean
     */
    public function isOwner($User)
    {
        return $User->getId() == $this->User->getId();
    }


    const PERMISSION_VIEW_CALENDAR = 'viewCalendar';
    const PERMISSION_EDIT_CALENDAR = 'editCalendar';
    const PERMISSION_DELETE_CALENDAR = 'deleteCalendar';

    const PERMISSION_ADD_EVENT = 'addEvent';
    const PERMISSION_REMOVE_EVENT = 'removeEvent';
    const PERMISSION_EDIT_EVENT = 'editEvent';
}
