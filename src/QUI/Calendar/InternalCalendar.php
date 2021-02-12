<?php
/**
 * This file contains QUI\Calendar\Calendar
 */

namespace QUI\Calendar;

use function DusanKasan\Knapsack\last;
use Eluceo\iCal\Component\Calendar;
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
     *
     * @throws Exception - given calendar data belongs to an external calendar
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
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't insert event into the database
     */
    public function addCalendarEvent($title, $desc, $start, $end)
    {
        $this->checkPermission(self::PERMISSION_ADD_EVENT);

        try {
            QUI::getDataBase()->insert(Handler::tableCalendarsEvents(), [
                'title'      => $title,
                'desc'       => $desc,
                'start'      => $start,
                'end'        => $end,
                'calendarid' => $this->getId()
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        return QUI::getDataBase()->getPDO()->lastInsertId('eventid');
    }


    /**
     * Adds multiple events at once to the calendar.
     *
     * @param \QUI\Calendar\Event[] $events
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to add events to the calendar
     */
    public function addCalendarEvents($events)
    {
        $this->checkPermission(self::PERMISSION_ADD_EVENT);

        if (!is_array($events) || empty($events)) {
            return;
        }

        $sql      = "INSERT INTO ".Handler::tableCalendarsEvents()." (title, `desc`, start, `end`, calendarid) VALUES ";
        $lastElem = last($events);
        foreach ($events as $Event) {
            $data = implode(',', [
                "'".$Event->text."'",
                "'".$Event->description."'",
                $Event->start_date,
                $Event->end_date,
                $this->getId()
            ]);
            $sql  = $sql."($data)";
            if ($Event != $lastElem) {
                $sql = $sql.",";
            }
        }

        $sql = $sql.";";

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
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't update event in the database
     */
    public function editCalendarEvent($eventID, $title, $desc, $start, $end)
    {
        $this->checkPermission(self::PERMISSION_EDIT_EVENT);

        try {
            QUI::getDataBase()->update(Handler::tableCalendarsEvents(), [
                'title' => $title,
                'desc'  => $desc,
                'start' => $start,
                'end'   => $end
            ], [
                'eventid' => $eventID
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }
    }

    /**
     * Removes an event from the calendar.
     *
     * @param int $eventID - ID of the event to remove
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to remove events from the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't delete the event from the database
     */
    public function removeCalendarEvent($eventID)
    {
        $this->checkPermission(self::PERMISSION_REMOVE_EVENT);

        try {
            QUI::getDataBase()->delete(Handler::tableCalendarsEvents(), [
                'eventid' => $eventID
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }
    }

    /**
     * Converts a calendar and all its' events to iCal format
     *
     * @return string - The calendar in iCal format
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function toICal()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $Calendar = new Calendar($this->getId());
        $events   = $this->getEvents();

        foreach ($events as $Event) {
            try {
                $start = new \DateTime();
                $start->setTimestamp(strtotime($Event->start_date));

                $end = new \DateTime();
                $end->setTimestamp(strtotime($Event->end_date));
            } catch (\Exception $Exception) {
                // This should never happen since DateTime is instantiated without parameters
                // But just to be save, let's do this...
                QUI\System\Log::writeException($Exception);

                return "";
            }

            $CalendarEvent = new Event();

            $CalendarEvent->setDtStart($start)
                ->setDtEnd($end)
                ->setSummary($Event->text)
                ->setDescription($Event->description)
                ->setUniqueId($Event->id);

            $Calendar->addComponent($CalendarEvent);
        }

        return $Calendar->render();
    }


    /**
     * Converts the calendars events to JSON format
     *
     * @return string - The calendars events in JSON format
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function toJSON($options = [])
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $events = $this->getEvents($options);

        return json_encode($events);
    }


    /**
     * Returns all events in a calendar as an array
     *
     * @param array $options - filter options, optional
     * @return \QUI\Calendar\Event[] - array of events
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't fetch events' data from the database
     */
    public function getEvents($options = [])
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        try {
            $query = [
                'from'  => Handler::tableCalendarsEvents(),
                'where' => [
                    'calendarid' => (int)$this->getId()
                ]
            ];

            if (isset($options['showPastEvents']) && !$options['showPastEvents']) {
                $query['where']['start'] = [
                    'type'  => '>=',
                    'value' => time()
                ];
            }

            $eventsRaw = QUI::getDataBase()->fetch($query);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        $events = [];
        foreach ($eventsRaw as $event) {
            $events[] = \QUI\Calendar\Event::fromDatabaseArray($event);
        }

        return $events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getEventsForDate(\DateTime $Date, $ignoreTime)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestamp = $Date->getTimestamp();

        $where = [
            'calendarid' => (int)$this->getId(),
            'start'      => [
                'type'  => '<=',
                'value' => $timestamp
            ],
            'end'        => [
                'type'  => '>=',
                'value' => $timestamp
            ]
        ];

        if ($ignoreTime) {
            $DateImmutable = \DateTimeImmutable::createFromMutable($Date);

            $timestampDayStart = $DateImmutable->setTime(0, 0, 0)->getTimestamp();
            $timestampDayEnd   = $DateImmutable->setTime(23, 59, 59)->getTimestamp();

            // Values are correctly assigned and not swapped (!)
            // The event has to start before the end of this day
            // The event has to end after the start of this day
            $where['start']['value'] = $timestampDayEnd;
            $where['end']['value']   = $timestampDayStart;
        }

        try {
            $eventsRaw = QUI::getDataBase()->fetch([
                'from'  => Handler::tableCalendarsEvents(),
                'where' => $where
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            $eventsRaw = [];
        }

        $Events = new EventCollection();
        foreach ($eventsRaw as $event) {
            $Events->append(\QUI\Calendar\Event::fromDatabaseArray($event));
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getEventsBetweenDates(\DateTime $StartDate, \DateTime $EndDate, $ignoreTime)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestampStartDate = $StartDate->getTimestamp();
        $timestampEndDate   = $EndDate->getTimestamp();

        if ($ignoreTime) {
            $StartDateImmutable = \DateTimeImmutable::createFromMutable($StartDate);
            $timestampStartDate = $StartDateImmutable->setTime(0, 0, 0)->getTimestamp();

            $EndDateImmutable = \DateTimeImmutable::createFromMutable($EndDate);
            $timestampEndDate = $EndDateImmutable->setTime(23, 59, 59)->getTimestamp();
        }

        try {
            $eventsRaw = QUI::getDataBase()->fetch([
                'from'  => Handler::tableCalendarsEvents(),
                'where' => [
                    'calendarid' => (int)$this->getId(),
                    'start'      => [
                        'type'  => '<=',
                        'value' => $timestampEndDate
                    ],
                    'end'        => [
                        'type'  => '>=',
                        'value' => $timestampStartDate
                    ]
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            $eventsRaw = [];
        }


        $Events = new EventCollection();
        foreach ($eventsRaw as $event) {
            $Events->append(\QUI\Calendar\Event::fromDatabaseArray($event));
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
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

        $parameters = [
            ':calendarID' => (int)$this->getId(),
            ':startDate'  => time()
        ];

        if (is_numeric($amount) && $amount > -1) {
            $limit = (int)$amount;
            $sql   .= "LIMIT {$limit}";
        }

        $Statement = QUI::getDataBase()->getPDO()->prepare($sql);
        $Statement->execute($parameters);

        $eventsRaw = $Statement->fetchAll(\PDO::FETCH_ASSOC);

        $events = [];
        foreach ($eventsRaw as $event) {
            $events[] = \QUI\Calendar\Event::fromDatabaseArray($event);
        }

        return $events;
    }


    /**
     * @inheritdoc
     */
    public function isInternal()
    {
        return true;
    }
}
