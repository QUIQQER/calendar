<?php

use QUI\Calendar\Handler;

$lg = 'quiqqer/calendar';

if (empty($Site->getAttribute('calendarEdit.settings.id'))) {
    $Engine->assign([
        'errorMessage' => QUI::getLocale()->get($lg, 'quiqqer.frontend.calendar.message.calendar.missing')
    ]);

    return;
}

$calendarId = (int)$Site->getAttribute('calendarEdit.settings.id');

try {
    Handler::getCalendar($calendarId);
} catch (\Exception $Exception) {
    $Engine->assign([
        'errorMessage' => QUI::getLocale()->get($lg, 'quiqqer.frontend.calendar.message.calendar.invalid')
    ]);

    return;
}
