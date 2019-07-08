<?php

namespace QUI\Calendar;

use DateTime;
use QUI;
use QUI\Calendar\Exception\NoPermissionException;
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
     * The calendars color as hex value
     *
     * @var string
     */
    protected $color;

    /**
     * Calendar constructor. Returns a calendar object for the given calendar id.
     *
     * @param int - $calendarId
     *
     * @throws QUI\Calendar\Exception - Calendar does not exist
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't fetch the calendar's data from the database
     */
    public function __construct($calendarId)
    {
        try {
            $result = QUI::getDataBase()->fetch(
                [
                    'from'  => Handler::tableCalendars(),
                    'where' => [
                        'id' => (int)$calendarId
                    ],
                    'limit' => 1
                ]
            );
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\DatabaseException();
        }


        if (!isset($result[0])) {
            throw new QUI\Calendar\Exception(
                [
                    'quiqqer/calendar',
                    'exception.calendar.not_found'
                ]
            );
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
        $this->name = $data['name'];

        try {
            $this->User = QUI::getUsers()->get($data['userid']);
        } catch (QUI\Exception $Exception) {
            // The user specified in the calendar's data does not exist (anymore)
            $this->User = QUI::getUsers()->getNobody();
        }

        $this->isPublic = $data['isPublic'] == 1 ? true : false;
        $this->color    = $data['color'];
    }


    /**
     * Edits the calendars values
     *
     * @param $name     - The new calendar name
     * @param $isPublic - Is the calendar public?
     * @param $color    - The calendars color
     *
     * @throws NoPermissionException - Current user isn't allowed to edit the calendar
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't update the event in the database
     */
    public function editCalendar($name, $isPublic, $color)
    {
        $this->checkPermission(AbstractCalendar::PERMISSION_EDIT_CALENDAR);

        $this->name     = $name;
        $this->isPublic = $isPublic;
        $this->color    = $color;

        try {
            QUI::getDataBase()->update(
                Handler::tableCalendars(),
                [
                    'name'     => $name,
                    'isPublic' => $isPublic,
                    'color'    => $color
                ],
                ['id' => $this->getId()]
            );
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\DatabaseException();
        }
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
     * Returns all events for a given date (day).
     *
     * The second parameter determines whether the exact point in time should be used or the entire day.
     *
     * @param DateTime $Date
     * @param boolean  $ignoreTime
     *
     * @return  Collection
     * @example
     * passed date object: 20.04.2042 13:37
     * second parameter true: Returns all events that occur on 20.04.2042
     * second parameter false: Returns all events that occur on 20.04.2042 at 13:37
     */
    abstract public function getEventsForDate(DateTime $Date, $ignoreTime);

    /**
     * Returns all events between two given dates.
     *
     * The second parameter determines whether the exact point in time should be used or the entire day.
     *
     * @param DateTime $IntervalStart
     * @param DateTime $IntervalEnd
     * @param boolean  $ignoreTime
     *
     * @param          $limit
     *
     * @return  Collection
     * @example
     * passed date objects: 20.04.2042 13:37 and 06.09.2042 04:20
     * second parameter true: Returns all events that occur between 20.04.2042 00:00 and 06.09.2042 23:59
     * second parameter false: Returns all events that occur between 20.04.2042 13:37 and 06.09.2042 04:20
     */
    abstract public function getEventsBetweenDates(DateTime $IntervalStart, DateTime $IntervalEnd, $ignoreTime, $limit);

    /**
     * Returns the calendars color in hex format.
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Returns the specified amount of upcoming events
     *
     * @param int - Amount of upcoming events to get. Leave empty or set to -1 to get all upcoming events.
     *
     * @return EventCollection - EventCollection of upcoming events
     */
    abstract public function getUpcomingEvents($amount = -1);

    /**
     * Converts the calendars information to an array. Does not include events.
     *
     * @return array
     *
     * @throws NoPermissionException - Current user isn't allowed to view the calendar
     */
    public function toArray()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return [
            'isPublic'     => $this->isPublic(),
            'calendarname' => $this->getName(),
            'id'           => $this->getId(),
            'color'        => $this->getColor(),
        ];
    }


    /**
     * Returns if a user can perform a specified action on the calendar.
     * By default the session user is used.
     * By passing a user as the second argument the permissions for a third user can be checked.
     *
     * @param      $permission - Name of the permission to check
     * @param User $User       - The user to check the permission for (Session User by default)
     *
     * @return boolean
     */
    public function hasPermission($permission, User $User = null)
    {
        try {
            static::checkPermission($permission, $User);
        } catch (NoPermissionException $Exception) {
            return false;
        }

        return true;
    }


    /**
     * Checks if a user can perform a specified action on the calendar.
     * Throws an exception if the user doesn't have the permission.
     * By default the session user is used.
     * By passing a user as the second argument the permissions for a third user can be checked.
     *
     * @param      $permission - Name of the permission to check
     * @param User $User       - The user to check the permission for (Session User by default)
     *
     * @return boolean
     *
     * @throws NoPermissionException
     */
    public function checkPermission($permission, User $User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUsers()->getUserBySession();
        }

        // Super User
        if ($User->isSU()) {
            return true;
        }

        // System User (e.g. CRON or CLI)
        if (QUI::getUsers()->isSystemUser($User)) {
            return true;
        }

        if ($User->hasPermission(self::PERMISSION_IS_ADMIN)) {
            return true;
        }

        $localeGroup = 'quiqqer/calendar';

        switch ($permission) {
            // View the current calendar
            case self::PERMISSION_VIEW_CALENDAR:
                if ($this->isOwner($User) || $this->isPublic()) {
                    return true;
                }

                throw new NoPermissionException(
                    [
                        $localeGroup,
                        'exception.calendar.permission.view'
                    ]
                );

                break;

            // Create a calendar
            case self::PERMISSION_CREATE_CALENDAR:
                if ($User->hasPermission($permission)) {
                    return true;
                }

                throw new NoPermissionException(
                    [
                        $localeGroup,
                        'exception.calendar.permission.create'
                    ]
                );

                break;

            case self::PERMISSION_ADD_EVENT:
            case self::PERMISSION_DELETE_CALENDAR:
            case self::PERMISSION_EDIT_CALENDAR:
            case self::PERMISSION_EDIT_EVENT:
            case self::PERMISSION_REMOVE_EVENT:
                if ($this->isOwner($User) && $User->hasPermission($permission)) {
                    return true;
                }

                $Locale         = QUI::getLocale();
                $permissionName = $Locale->get($localeGroup, 'permission.' . $permission);
                $message        = $Locale->get(
                    $localeGroup,
                    'exception.calendar.permission.message.general',
                    ['permission' => $permissionName]
                );

                throw new NoPermissionException($message);
        }

        throw new NoPermissionException(
            [
                $localeGroup,
                'exception.calendar.permission.edit'
            ]
        );
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
     * Returns true if the Calendar is internal, or false if something else (e.g. external)
     *
     * @return boolean
     */
    abstract public function isInternal();


    /**
     * Checks if the calendar is internal, throws Exception if not
     *
     * @throws Exception
     */
    public function checkInternal()
    {
        if (!$this->isInternal()) {
            throw new Exception(
                [
                    'quiqqer/calendar',
                    'exception.calendar.notInternal'
                ]
            );
        }
    }


    /**
     * Checks if the calendar is external, throws Exception if not
     *
     * @throws Exception
     */
    public function checkExternal()
    {
        if ($this->isInternal()) {
            throw new Exception(
                [
                    'quiqqer/calendar',
                    'exception.calendar.notExternal'
                ]
            );
        }
    }


    /**
     * Returns the share URL for the calendar.
     * By default the URL is returned for the current session's user.
     *
     * If a user is passed as an argument the share URL for this user is returned.
     *
     * @param User|null $User - The user to get the share URL for
     *
     * @return string
     *
     * @throws NoPermissionException - The user has no permission to view the calendar
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't read/write from/to database
     * @throws QUI\Calendar\Exception\ShareException - Couldn't generate a share-hash (missing entropy)
     */
    public function getShareUrl(User $User = null)
    {
        return ShareHandler::getShareUrlForCalendar($this, $User);
    }


    /**
     * Calendar specific permissions
     */
    const PERMISSION_VIEW_CALENDAR = 'quiqqer.calendar.view';
    const PERMISSION_EDIT_CALENDAR = 'quiqqer.calendar.edit';
    const PERMISSION_DELETE_CALENDAR = 'quiqqer.calendar.delete';
    const PERMISSION_CREATE_CALENDAR = 'quiqqer.calendar.create';

    /**
     * Admin Permission:
     * Allows the user to edit every calendar and event.
     */
    const PERMISSION_IS_ADMIN = 'quiqqer.calendar.admin';

    /**
     * Event-specific permissions
     */
    const PERMISSION_ADD_EVENT = 'quiqqer.calendar.event.create';
    const PERMISSION_REMOVE_EVENT = 'quiqqer.calendar.event.delete';
    const PERMISSION_EDIT_EVENT = 'quiqqer.calendar.event.edit';
}
