<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar;

/**
 * Class Collection
 *
 * @package QUI\Calendar
 */
class Collection extends \QUI\Collection
{
    protected $allowed = [Event::class];
}
