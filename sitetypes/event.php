<?php

$eventID = $Site->getAttribute('event.settings.id');
$Event   = \QUI\Calendar\EventManager::getEventById($eventID);

$values = array(
    'Event' => $Event,

    'EventLocale' => array(
        'eventNotFound' => $Locale->get('quiqqer/calendar', 'sitetypes.event.error.notFound'),
        'details'       => $Locale->get('quiqqer/calendar', 'sitetypes.event.details'),
        'months'        => array(
            $Locale->get('quiqqer/calendar-controls', 'month.01'),
            $Locale->get('quiqqer/calendar-controls', 'month.02'),
            $Locale->get('quiqqer/calendar-controls', 'month.03'),
            $Locale->get('quiqqer/calendar-controls', 'month.04'),
            $Locale->get('quiqqer/calendar-controls', 'month.05'),
            $Locale->get('quiqqer/calendar-controls', 'month.06'),
            $Locale->get('quiqqer/calendar-controls', 'month.07'),
            $Locale->get('quiqqer/calendar-controls', 'month.08'),
            $Locale->get('quiqqer/calendar-controls', 'month.09'),
            $Locale->get('quiqqer/calendar-controls', 'month.10'),
            $Locale->get('quiqqer/calendar-controls', 'month.11'),
            $Locale->get('quiqqer/calendar-controls', 'month.12'),
        ),
        'days'          => array(
            $Locale->get('quiqqer/calendar-controls', 'sunday'),
            $Locale->get('quiqqer/calendar-controls', 'monday'),
            $Locale->get('quiqqer/calendar-controls', 'tuesday'),
            $Locale->get('quiqqer/calendar-controls', 'wednesday'),
            $Locale->get('quiqqer/calendar-controls', 'thursday'),
            $Locale->get('quiqqer/calendar-controls', 'friday'),
            $Locale->get('quiqqer/calendar-controls', 'saturday'),
        )
    )
);


if (!is_null($Event)) {
    $values = array_merge(
        $values,
        array(
            'EventStart' => new DateTime($Event->start_date),
            'EventEnd'   => new DateTime($Event->end_date),
        )
    );
}

$Locale = \QUI::getLocale();
$Engine->assign($values);
