<?php
/**
 * This file contains QUI\Calendar\Calendar
 */
namespace QUI\Calendar;

use QUI;

/**
 * Class Calendar
 * one Calendar
 *
 * @package QUI\Calendar
 */
class Calendar
{
    private $calendarsTable = QUI_DB_PRFX . 'calendars';
    private $eventsTable    = QUI_DB_PRFX . 'calendars_events';

    private $id;
    private $name;
    private $user = null;

    /**
     * Calendar constructor. Returns a calendar object for the given calendar id.
     * @param int - $calendarId
     */
    public function __construct($calendarId)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->calendarsTable,
            'where' => array(
                'id' => (int)$calendarId
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            return false;
        }

        $result = $result[0];

        $this->id   = $result['id'];
        $this->name = $result['name'];
        if (!is_null($result['userid'])) {
            $this->user = QUI::getUsers()->get($result['userid']);
        }

        return true;
    }

    /**
     * @param $name
     * @param null|QUI\Users\User $user
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
            $this->user = $user;
        }

        QUI::getDataBase()->update(
            $this->calendarsTable,
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
     */
    public function addCalendarEvent($title, $desc, $start, $end)
    {

        $data = array(
            'title' => $title,
            'desc'  => $desc,
            'start' => $start,
            'end'   => $end
        );

        if (!$this->isGlobal()) {
            $data['userid'] = $this->getUser()->getId();
        }

        QUI::getDataBase()->insert($this->eventsTable, $data);
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
        QUI::getDataBase()->update($this->eventsTable, array(
            'title' => $title,
            'desc'  => $desc,
            'start' => $start,
            'end'   => $end
        ), array(
            'id' => $eventID
        ));
    }

    /**
     * Removes an event from the calendar.
     *
     * @param int $eventID - ID of the event to remove
     */
    public function removeCalendarEvent($eventID)
    {
        QUI::getDataBase()->delete($this->eventsTable, array(
            'eventid' => $eventID
        ));
    }

    public function toICal()
    {
    }

    /**
     * Returns the calendars ID.
     *
     * @return int
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


    public function toArray()
    {
        return array(
            'isGlobal' => $this->isGlobal(),
            'calendarname' => $this->getName(),
            'id' => $this->getId()
        );
    }
}
