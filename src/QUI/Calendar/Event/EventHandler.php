<?php

namespace QUI\Calendar\Event;

class EventHandler
{
    public static function onAdminLoadFooter()
    {
        echo "<script src='" . URL_OPT_DIR . "quiqqer/calendar/bin/initMenuEntries.js'></script>";
    }
}
