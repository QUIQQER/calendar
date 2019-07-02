<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar\Event;

use QUI\Calendar\Event;

/**
 * Class Collection
 *
 * @package QUI\Calendar
 */
class Collection extends \QUI\Collection
{
    protected $allowed = [Event::class];
}
