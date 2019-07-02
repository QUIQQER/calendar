<?php
/**
 * This file contains QUI\Calendar\Calendar
 */

namespace QUI\Calendar;

use DateTime;
use DateTimeImmutable;
use function DusanKasan\Knapsack\last;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use PDO;
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
     * @param string $desc  - Event description
     * @param int    $start - Unix timestamp when the event starts
     * @param int    $end   - Unix timestamp when the event ends
     * @param string $url   - Link to further information about the event
     *
     * @return int - The ID the event got assigned from the database
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't insert event into the database
     */
    public function addCalendarEvent($title, $desc, $start, $end, $url = "")
    {
        $this->checkPermission(self::PERMISSION_ADD_EVENT);

        try {
            QUI::getDataBase()->insert(
                Handler::tableCalendarsEvents(),
                [
                    'title'      => $title,
                    'desc'       => $desc,
                    'start'      => $start,
                    'end'        => $end,
                    'url'        => $url,
                    'calendarid' => $this->getId()
                ]
            );
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

        $sql      = "INSERT INTO " . Handler::tableCalendarsEvents() . " (title, `desc`, `url`, start, `end`, calendarid) VALUES ";
        $lastElem = last($events);
        foreach ($events as $Event) {
            $data = implode(
                ',',
                [
                    "'" . $Event->getTitle() . "'",
                    "'" . $Event->getDescription() . "'",
                    "'" . $Event->getUrl() . "'",
                    $Event->start_date,
                    $Event->end_date,
                    $this->getId()
                ]
            );
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
     * @param int    $eventID - ID of the event to edit
     * @param string $title   - Event title
     * @param string $desc    - Event description
     * @param int    $start   - Unix timestamp when the event starts
     * @param int    $end     - Unix timestamp when the event ends
     * @param string $url     - Link to further information about the event
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\Database - Couldn't update event in the database
     */
    public function editCalendarEvent($eventID, $title, $desc, $start, $end, $url)
    {
        $this->checkPermission(self::PERMISSION_EDIT_EVENT);

        try {
            QUI::getDataBase()->update(
                Handler::tableCalendarsEvents(),
                [
                    'title' => $title,
                    'desc'  => $desc,
                    'start' => $start,
                    'end'   => $end,
                    'url'   => $url
                ],
                [
                    'eventid' => $eventID
                ]
            );
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
            QUI::getDataBase()->delete(
                Handler::tableCalendarsEvents(),
                [
                    'eventid' => $eventID
                ]
            );
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
                $start = new DateTime();
                $start->setTimestamp(strtotime($Event->start_date));

                $end = new DateTime();
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
                ->setUrl($Event->url)
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
    public function toJSON()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $events = $this->getEvents();

        return json_encode($events);
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getEventsForDate(DateTime $Date, $ignoreTime)
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
            $DateImmutable = DateTimeImmutable::createFromMutable($Date);

            $timestampDayStart = $DateImmutable->setTime(0, 0, 0)->getTimestamp();
            $timestampDayEnd   = $DateImmutable->setTime(23, 59, 59)->getTimestamp();

            // Values are correctly assigned and not swapped (!)
            // The event has to start before the end of this day
            // The event has to end after the start of this day
            $where['start']['value'] = $timestampDayEnd;
            $where['end']['value']   = $timestampDayStart;
        }

        try {
            $eventsRaw = QUI::getDataBase()->fetch(
                [
                    'from'  => Handler::tableCalendarsEvents(),
                    'where' => $where
                ]
            );
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            $eventsRaw = [];
        }

        $Events = new Collection();
        foreach ($eventsRaw as $event) {
            $Events->append(Event\Utils::fromDatabaseArray($event));
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @param DateTime      $IntervalStart
     * @param DateTime|null $IntervalEnd
     * @param bool          $ignoreTime
     * @param int           $limit
     * @param bool          $inflateRecurringEvents - Create child-events for recurring events (default) or not
     *
     * @return Collection
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getEventsBetweenDates(
        DateTime $IntervalStart,
        DateTime $IntervalEnd = null,
        $ignoreTime = true,
        $limit = 1000,
        bool $inflateRecurringEvents = true
    ) {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        if (is_null($IntervalEnd)) {
            $IntervalEnd = new DateTime();
            $IntervalEnd->setTimestamp(PHP_INT_MAX);
        }

        $timestampIntervalStart = $IntervalStart->getTimestamp();
        $timestampIntervalEnd   = $IntervalEnd->getTimestamp();

        if ($ignoreTime) {
            $IntervalStartImmutable = DateTimeImmutable::createFromMutable($IntervalStart);
            $timestampIntervalStart = $IntervalStartImmutable->setTime(0, 0, 0, 0)->getTimestamp();

            $IntervalEndImmutable = DateTimeImmutable::createFromMutable($IntervalEnd);
            $timestampIntervalEnd = $IntervalEndImmutable->setTime(23, 59, 59, 999999)->getTimestamp();
        }

        $tableEvents     = Handler::tableCalendarsEvents();
        $tableRecurrence = Handler::tableCalendarsEventsRecurrence();

        $sql = "
            SELECT events.*, 
                   recurrence_end, 
                   recurrence_interval 
            FROM {$tableEvents} events
                LEFT JOIN {$tableRecurrence} recurrence
                    ON events.eventid = recurrence.eventid
            WHERE 
                calendarid = :calendar_id AND
                events.start <= :interval_end AND
                ((                     
                    events.end >= :interval_start AND 
                    recurrence_end IS NULL
                ) OR (
                    recurrence_end >= :interval_start
                ))
            ORDER BY start ASC
        ";
        // LIMIT happens by using "static::processEventDatabaseData()" below

        $Statement = QUI::getDataBase()->getPDO()->prepare($sql);
        $Statement->execute(
            [
                ':calendar_id'    => (int)$this->getId(),
                ':interval_start' => $timestampIntervalStart,
                ':interval_end'   => $timestampIntervalEnd
            ]
        );

        $eventsRaw = $Statement->fetchAll(PDO::FETCH_ASSOC);

        $EventCollection = new QUI\Calendar\Event\Collection();
        foreach ($eventsRaw as $eventRaw) {
            try {
                $Event = QUI\Calendar\Event\Utils::createEventFromDatabaseArray($eventRaw);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                continue;
            }

            $EventCollection->append($Event);
        }

        if ($inflateRecurringEvents) {
            try {
                QUI\Calendar\Event\Utils::inflateRecurringEvents($EventCollection, $limit);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $EventCollection;
    }


    /**
     * @inheritDoc
     *
     * @param int $amount
     *
     * @return Collection
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getUpcomingEvents($amount = 1000)
    {
        return $this->getEventsBetweenDates(new DateTime(), null, false, $amount);
    }


    /**
     * @inheritdoc
     */
    public function isInternal()
    {
        return true;
    }
}
