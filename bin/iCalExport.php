<?php

if (!isset($_GET['calendar']) && !isset($_GET['hash'])) {
    exit;
}

// Import QUIQQER Bootstrap
define('QUIQQER_SYSTEM', true);
$packagesDir = str_replace('quiqqer/calendar/bin', '', dirname(__FILE__));
require_once $packagesDir . '/header.php';

// Get calendar ID from GET parameter and sanitize it to be just a number
if (isset($_GET['calendar'])) {
    $calendarID = filter_var($_GET['calendar'], FILTER_SANITIZE_NUMBER_INT);
    $method     = 'id';
}

if (isset($_GET['hash'])) {
    $hash   = filter_var($_GET['hash'], FILTER_SANITIZE_STRING);
    $method = 'hash';
}

try {
    switch ($method) {
        case 'id':
            $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);
            break;

        case 'hash':
            $Calendar = \QUI\Calendar\ShareHandler::getCalendarFromHash($hash);
            break;

        default:
            exit;
    }

    $iCal = $Calendar->toICal();

    $calendarID = $Calendar->getId();
    $calendarName = str_replace(' ', '_', $Calendar->getName());

    // Return file download
    header("Content-Type: text/calendar; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$calendarID-$calendarName.ics\"");
    header("Content-Length: " . strlen($iCal));

    echo $iCal;
} catch (\Exception $exception) {
    echo "Houston, we got a problem";
    exit;
}

exit;
