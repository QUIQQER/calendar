<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar\Event;

use QUI\Calendar\Event;
use QUI\Collection;

/**
 * Class EventCollection
 *
 * @package QUI\Calendar
 */
class EventCollection extends Collection
{
    protected $allowed = [Event::class];

    public function sortByStartDate()
    {
        \usort($this->children, [EventUtils::class, 'sortEventArrayByStartDateComparisonFunction']);
    }

    /**
     * Converts all events in the collection to DHTMLX scheduler format.
     *
     * @return array
     */
    public function toSchedulerFormat(): array
    {
        return $this->map(function ($Event) {
            /** @var Event $Event */
            return $Event->toSchedulerFormat();
        });
    }
}
