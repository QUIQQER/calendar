<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar\Event;

use QUI\Calendar\Event;
use QUI\Calendar\Exception\InvalidArgumentException;

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
    protected $recurrenceEnd;

    /**
     * RecurringEvent constructor.
     *
     * @param string    $title
     * @param \DateTime $start
     * @param \DateTime $end
     * @param string    $recurrenceInterval
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $title,
        \DateTime $start,
        \DateTime $end,
        string $recurrenceInterval
    ) {
        parent::__construct($title, $start, $end);

        $this->setRecurrenceInterval($recurrenceInterval);
    }

    /**
     * Returns the recurrence interval.
     * This is one of the "INTERVAL_"-constants.
     *
     * @return string
     */
    public function getRecurrenceInterval(): bool
    {
        return $this->recurrenceInterval;
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
        return $this->recurrenceEnd;
    }

    /**
     * Sets the end of the events recurrence.
     *
     * @param \DateTime $recurrenceEnd
     *
     * @return RecurringEvent
     */
    public function setRecurrenceEnd(\DateTime $recurrenceEnd): RecurringEvent
    {
        $this->recurrenceEnd = $recurrenceEnd;

        return $this;
    }
}
