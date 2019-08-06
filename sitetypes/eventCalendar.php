<?php

$events      = [];
$calendarIDs = $Site->getAttribute('calendar.settings.ids');

if (!is_array($calendarIDs)) {
    $calendarIDs = explode(',', $calendarIDs);
}

$Engine->assign([]);
