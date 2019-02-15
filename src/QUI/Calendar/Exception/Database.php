<?php

namespace QUI\Calendar\Exception;

use QUI;

/**
 * Thrown when something (bad) happens with/inside the database
 */
class Database extends QUI\Calendar\Exception
{
    public function __construct($message = null, int $code = 0, array $context = array())
    {
        if (is_null($message)) {
            QUI::getLocale()->get('quiqqer/calendar', 'exception.database.general');
        }

        parent::__construct($message, $code, $context);
    }
}
