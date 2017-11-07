<?php

// Import QUIQQER Bootstrap
define('QUIQQER_SYSTEM', true);
$packagesDir = str_replace('quiqqer/calendar/bin', '', dirname(__FILE__));
require_once $packagesDir . '/header.php';

// Get calendar ID from GET parameter and sanitize it to be just a number
$calendarID = filter_var($_GET['calendar'], FILTER_SANITIZE_NUMBER_INT);

try {
    $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);

    // Check if user is allowed to view ergo download the calendar
    $Calendar->checkPermission($Calendar::PERMISSION_VIEW_CALENDAR);

    $iCal = $Calendar->toICal();

    $calendarID   = $Calendar->getId();
    $calendarName = str_replace(' ', '_', $Calendar->getName());

    // Return file download
    header("Content-Type: text/calendar; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$calendarID-$calendarName.ics\"");
    header("Content-Length: " . strlen($iCal));

    echo $iCal;
} catch (Exception $exception) {
}

exit;
