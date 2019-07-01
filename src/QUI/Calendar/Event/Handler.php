<?php

namespace QUI\Calendar;

class Handler
{
    public static function onAdminLoadFooter()
    {
        echo "<script src='" . URL_OPT_DIR . "quiqqer/calendar/bin/initMenuEntries.js'></script>";
    }
}
