<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar;

use QUI\Collection;

/**
 * Class EventCollection
 * @package QUI\Calendar
 */
class EventCollection extends Collection
{
    protected $allowed = [Event::class];
}
