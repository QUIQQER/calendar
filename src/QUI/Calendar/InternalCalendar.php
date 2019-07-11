<?php
/**
 * This file contains QUI\Calendar\Calendar
 */

namespace QUI\Calendar;

use DateTime;
use DateTimeImmutable;
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
    protected function construct(array $data)
    {
        if ($data['isExternal'] == 1) {
            throw new Exception("Calendar with ID {$this->getId()} is external but was created as internal");
        }

        parent::construct($data);
    }


    /**
     * Adds an event to the calendar.
     * The event is not allowed to have an ID yet.
     * The calendar ID has to be set already.
     *
     * On success, the event gets it's ID assigned (parameter passed by reference).
     * On error, the event's ID does not change.
     *
     * @param \QUI\Calendar\Event $Event - The event to add
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't insert event into the database
     * @throws QUI\Calendar\Exception\InvalidArgumentException - Event already has it's own ID or calendar ID.
     */
    public function addEvent(\QUI\Calendar\Event &$Event): void
    {
        $this->checkPermission(self::PERMISSION_ADD_EVENT);

        if (!\is_null($Event->getId())) {
            $message = "The event already has the ID '{$Event->getId()}'. It should have none yet.";
            throw new QUI\Calendar\Exception\InvalidArgumentException($message);
        }

        $this->writeEventToDatabase($Event);
    }


    /**
     * Updates/Overwrites an event in the calendar.
     * The event is must have an ID yet.
     *
     * @param \QUI\Calendar\Event $Event - The event to add
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't insert event into the database
     * @throws QUI\Calendar\Exception\InvalidArgumentException - Event already has it's own ID or calendar ID.
     */
    public function updateEvent(\QUI\Calendar\Event $Event)
    {
        $this->checkPermission(self::PERMISSION_EDIT_EVENT);

        if (\is_null($Event->getId())) {
            $message = "The event has no ID. Can not update the event in the database.";
            throw new QUI\Calendar\Exception\InvalidArgumentException($message);
        }

        $this->writeEventToDatabase($Event);
    }

    /**
     * Removes the given event from the calendar/database.
     *
     * Throws an exception if something goes wrong.
     *
     * @param \QUI\Calendar\Event $Event
     *
     * @throws Exception\DatabaseException
     * @throws Exception\NoPermissionException
     */
    public function removeEvent(\QUI\Calendar\Event $Event): void
    {
        $this->checkPermission(self::PERMISSION_REMOVE_EVENT);

        $PDO = QUI::getPDO();

        $PDO->beginTransaction();
        try {
            $eventData      = $Event->toArrayForDatabase();
            $tableEventData = Handler::tableCalendarsEvents();

            QUI::getDataBase()->delete($tableEventData, $eventData[$tableEventData]);

            // Recurring event?
            if ($Event instanceof QUI\Calendar\Event\RecurringEvent) {
                $tableRecurringEventData = Handler::tableCalendarsEventsRecurrence();

                QUI::getDataBase()->delete($tableRecurringEventData, $eventData[$tableRecurringEventData]);
            }
        } catch (QUI\Database\Exception $Exception) {
            // Undo the previous queries
            $PDO->rollBack();

            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\DatabaseException();
        }
        // Everything is fine, now commit the data to the database
        $PDO->commit();
    }

    /**
     * Converts a calendar and all its' events to iCal format
     *
     * @return string - The calendar in iCal format
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     */
    public function toICal(): string
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
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     */
    public function getEventsForDate(
        DateTime $Date,
        bool $ignoreTime,
        int $limit = 1000
    ): QUI\Calendar\Event\EventCollection {
        $StartDate = clone $Date;
        $EndDate   = clone $Date;

        if ($ignoreTime) {
            $StartDate->setTime(0, 0, 0, 0);
            $EndDate->setTime(23, 59, 59, 999999);
        }

        return $this->getEventsBetweenDates(
            $StartDate,
            $EndDate,
            $ignoreTime,
            $limit
        );
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
     * @return QUI\Calendar\Event\EventCollection
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     */
    public function getEventsBetweenDates(
        DateTime $IntervalStart,
        DateTime $IntervalEnd = null,
        bool $ignoreTime = true,
        int $limit = 1000,
        bool $inflateRecurringEvents = true
    ): QUI\Calendar\Event\EventCollection {
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

        // Get all normal (first condition) and recurring events (second condition).
        // An event is normal if it has no recurrence_end (IS NULL)
        // If it's normal is has to start before the end of the requested interval
        //
        // An event is recurring if it has a recurrence_end set
        // Since we (currently) can not calculate how often an event recurs and if it recurs in our interval via SQL,
        // we query all events, except the once with a recurrence_end before the start of the requested interval.
        // The filtering happens later via PHP-logic ("EventUtils::inflateRecurringEvents()").
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
        // LIMIT happens by using "EventUtils::inflateRecurringEvents()" below

        $Statement = QUI::getDataBase()->getPDO()->prepare($sql);
        $Statement->execute(
            [
                ':calendar_id'    => (int)$this->getId(),
                ':interval_start' => $timestampIntervalStart,
                ':interval_end'   => $timestampIntervalEnd
            ]
        );

        $eventsRaw = $Statement->fetchAll(PDO::FETCH_ASSOC);

        $EventCollection = new QUI\Calendar\Event\EventCollection();
        foreach ($eventsRaw as $eventRaw) {
            try {
                $Event = QUI\Calendar\Event\EventUtils::createEventFromDatabaseArray($eventRaw);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                continue;
            }

            $EventCollection->append($Event);
        }

        // Create/"Inflate" all recurring events
        if ($inflateRecurringEvents) {
            try {
                QUI\Calendar\Event\EventUtils::inflateRecurringEvents($EventCollection, $limit, $IntervalEnd);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        if ($EventCollection->length() > $limit) {
            $eventCounter = 0;
            // Remove events that are out of range and reduce the event amount to the given limit.
            // The events are already sorted properly by "inflateRecurringEvents()" above.
            $EventCollection = $EventCollection->filter(function ($Event) use (
                &$eventCounter,
                $limit,
                $IntervalEnd,
                $IntervalStart
            ) {
                /** @var \QUI\Calendar\Event $Event */
                return (
                    $Event->getStartDate() <= $IntervalEnd &&
                    $Event->getEndDate() >= $IntervalStart &&
                    ++$eventCounter <= $limit
                );
            });
        }

        return $EventCollection;
    }


    /**
     * @inheritDoc
     *
     * @param int $amount
     *
     * @return QUI\Calendar\Event\EventCollection
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     */
    public function getUpcomingEvents(int $amount = 1000): QUI\Calendar\Event\EventCollection
    {
        return $this->getEventsBetweenDates(new DateTime(), null, false, $amount);
    }


    /**
     * @inheritdoc
     */
    public function isInternal(): bool
    {
        return true;
    }


    /**
     * (Over)Writes an event in the database.
     * If the event has an ID, assigned the event is overwritten in the database.
     * If the event has no ID, it's added to the database.
     *
     * On success, the event gets it's ID assigned (parameter passed by reference).
     * On error, the event's ID does not change.
     *
     * @param \QUI\Calendar\Event $Event - The event to add
     *
     * @throws QUI\Calendar\Exception\DatabaseException - Couldn't insert event into the database
     * @throws QUI\Calendar\Exception\InvalidArgumentException - Event already has it's own ID or calendar ID.
     */
    protected function writeEventToDatabase(\QUI\Calendar\Event &$Event): void
    {
        if ($Event->getCalendarId() != $this->getId()) {
            $message = "The event's calendar ID '{$Event->getCalendarId()}' doesn't match the calendar's ID '{$this->getId()}'";
            throw new QUI\Calendar\Exception\InvalidArgumentException($message);
        }

        $EventClone = clone $Event;

        $PDO = QUI::getPDO();

        $PDO->beginTransaction();
        try {
            $tableEventData = Handler::tableCalendarsEvents();

            QUI::getDataBase()->replace($tableEventData, $Event->toArrayForDatabase()[$tableEventData]);

            $Event->setId($PDO->lastInsertId('eventid'));

            // Recurring event?
            if ($Event instanceof QUI\Calendar\Event\RecurringEvent) {
                $tableRecurringEventData = Handler::tableCalendarsEventsRecurrence();

                QUI::getDataBase()->replace(
                    $tableRecurringEventData,
                    // Re-fetching the data here because the event's id is set now
                    $Event->toArrayForDatabase()[$tableRecurringEventData]
                );
            }
        } catch (QUI\Database\Exception $Exception) {
            // Undo the previous queries
            $PDO->rollBack();

            // Reset the event
            $Event = $EventClone;

            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\DatabaseException();
        }
        // Everything is fine, now commit the data to the database
        $PDO->commit();
    }
}
