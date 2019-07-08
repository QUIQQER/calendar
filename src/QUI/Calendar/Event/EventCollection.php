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
}
