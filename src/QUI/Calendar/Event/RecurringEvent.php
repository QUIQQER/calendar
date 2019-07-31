<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar\Event;

use QUI\Calendar\Event;
use QUI\Calendar\Exception\InvalidArgumentException;
use QUI\Utils\Convert;

/**
 * Class RecurringEvent
 *
 * @package QUI\Calendar\Event
 */
class RecurringEvent extends Event
{
    // region constants
    /**
     * @var string - Value for yearly interval
     */
    const INTERVAL_YEAR = "year";

    /**
     * @var string - Value for monthly interval
     */
    const INTERVAL_MONTH = "month";

    /**
     * @var string - Value for weekly interval
     */
    const INTERVAL_WEEK = "week";

    /**
     * @var string - Value for daily interval
     */
    const INTERVAL_DAY = "day";

    /**
     * @var string - Value for hourly interval
     */
    const INTERVAL_HOUR = "hour";
    // endregion


    /**
     * @var string - The events recurrence interval
     */
    protected $recurrenceInterval;

    /**
     * @var \DateTime - End of the recurrence
     */
    protected $RecurrenceEnd;

    /**
     * RecurringEvent constructor.
     *
     * @param string    $title
     * @param \DateTime $StartDate
     * @param \DateTime $EndDate
     * @param string    $recurrenceInterval
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $title,
        \DateTime $StartDate,
        \DateTime $EndDate,
        string $recurrenceInterval
    ) {
        parent::__construct($title, $StartDate, $EndDate);

        $this->setRecurrenceInterval($recurrenceInterval);
    }

    /**
     * Returns the recurrence interval.
     * This is one of the "INTERVAL_"-constants.
     *
     * @return string
     */
    public function getRecurrenceInterval(): string
    {
        return $this->recurrenceInterval;
    }


    /**
     * Returns the recurrence interval in iCal specification.
     *
     * @return string
     *
     * @example  'year' becomes 'yearly'
     *           'day' becomes 'daily'
     */
    public function getRecurrenceIntervalInIcalFormat(): string
    {
        $interval = $this->getRecurrenceInterval();

        if ($interval === self::INTERVAL_DAY) {
            $interval = 'daily';
        } else {
            $interval = $interval . 'ly';
        }

        return strtoupper($interval);
    }

    /**
     * Set the recurrence interval.
     * Returns null if an invalid interval is passed.
     *
     * @param string $recurrenceInterval - The interval; Has to be one of the "INTERVAL_"-constants
     *
     * @return RecurringEvent
     *
     * @throws InvalidArgumentException
     */
    public function setRecurrenceInterval($recurrenceInterval): RecurringEvent
    {
        switch ($recurrenceInterval) {
            case self::INTERVAL_YEAR:
            case self::INTERVAL_MONTH:
            case self::INTERVAL_WEEK:
            case self::INTERVAL_DAY:
            case self::INTERVAL_HOUR:
                $this->recurrenceInterval = $recurrenceInterval;

                return $this;
        }

        throw new InvalidArgumentException("The recurrence interval '{$recurrenceInterval}' is invalid.");
    }


    /**
     * Returns when the events recurrence ends.
     *
     * @return \DateTime|null
     */
    public function getRecurrenceEnd(): ?\DateTime
    {
        return $this->RecurrenceEnd;
    }

    /**
     * Sets the end of the events recurrence.
     *
     * @param \DateTime $RecurrenceEnd
     *
     * @return RecurringEvent
     */
    public function setRecurrenceEnd(\DateTime $RecurrenceEnd): RecurringEvent
    {
        $this->RecurrenceEnd = $RecurrenceEnd;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toArrayForDatabase(): array
    {
        $result = parent::toArrayForDatabase();

        // Table used for recurring events
        $tableRecurrence = \QUI\Calendar\Handler::tableCalendarsEventsRecurrence();

        $RecurrenceEnd = $this->getRecurrenceEnd();

        $result[$tableRecurrence]['eventid']             = $this->getId();
        $result[$tableRecurrence]['recurrence_interval'] = $this->getRecurrenceInterval();
        $result[$tableRecurrence]['recurrence_end']      = null;

        if ($RecurrenceEnd) {
            $result[$tableRecurrence]['recurrence_end'] = Convert::convertToMysqlDatetime($RecurrenceEnd);
        }

        return $result;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toSchedulerFormat(): array
    {
        // Get the parent's data
        $data = parent::toSchedulerFormat();

        // End of recurrence has to become the events end date
        $recurrenceEnd = $this->getRecurrenceEnd();

        if ($recurrenceEnd) {
            $recurrenceEnd = EventUtils::datetimeToSchedulerFormat($recurrenceEnd);
        } else {
            // No end date (value ist set according to http://disq.us/p/1jtwydk)
            $recurrenceEnd = '9999-01-01 00:00:00';
        }

        $data['end_date'] = $recurrenceEnd;

        // Format according to https://docs.dhtmlx.com/scheduler/recurring_events.html
        $data['rec_type'] = $this->getRecurrenceInterval() . '_1___';

        // Determines how long the event is to calculate the end of each event
        $data['event_length'] = $this->getEndDate()->getTimestamp() - $this->getStartDate()->getTimestamp();

        $data['event_pid'] = 0;

        // Custom attribute to later determine that the event is recurring via JS
        $data['recurring'] = true;

        return $data;
    }
}
