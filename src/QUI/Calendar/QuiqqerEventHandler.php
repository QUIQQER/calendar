<?php

namespace QUI\Calendar;

class QuiqqerEventHandler
{
    public static function onAdminLoadFooter()
    {
        echo "<script src='" . URL_OPT_DIR . "quiqqer/calendar/bin/initCalendarMenuEntries.js'></script>";
    }
}
