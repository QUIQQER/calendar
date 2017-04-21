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

        $this->id = $calendarId;

        $result = $result[0];

        $this->construct($result);
    }


    /**
     * Constructs the calendar from the given SQL result
     *
     * @param $data
     */
    protected function construct($data)
    {
        $this->name       = $data['name'];
        $this->User       = QUI::getUsers()->get($data['userid']);
        $this->isPublic   = $data['isPublic'] == 1 ? true : false;
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
    public function toArray()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

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


    const PERMISSION_VIEW_CALENDAR = 'viewCalendar';
    const PERMISSION_EDIT_CALENDAR = 'editCalendar';
    const PERMISSION_DELETE_CALENDAR = 'deleteCalendar';

    const PERMISSION_ADD_EVENT = 'addEvent';
    const PERMISSION_REMOVE_EVENT = 'removeEvent';
    const PERMISSION_EDIT_EVENT = 'editEvent';
}
