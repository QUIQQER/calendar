/**
 * Windows to add an event to a calendar
 *
 * @module package/quiqqer/calendar/bin/AddEventWindow
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI',
 * @require 'qui/controls/windows/Confirm'
 * @require 'Locale'
 * @require 'Mustache'
 * @require 'text!package/quiqqer/calendar/bin/AddEventWindow.html'
 * @require 'css!package/quiqqer/calendar/bin/AddEventWindow.css'
 */
define('package/quiqqer/calendar/bin/AddEventWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',

    'package/quiqqer/calendar-controls/bin/Source/Picker',
    'package/quiqqer/calendar-controls/bin/Source/Picker.Attach',
    'package/quiqqer/calendar-controls/bin/Source/Picker.Date',

    'package/quiqqer/calendar/bin/classes/EventHelper',

    'Locale',
    'Mustache',

    'text!package/quiqqer/calendar/bin/AddEventWindow.html',

    'css!package/quiqqer/calendar/bin/AddEventWindow.css',
    'css!package/quiqqer/calendar-controls/bin/Source/datepicker.css'

], function (QUI, QUIConfirm, Picker, PickerAttach, PickerDate, EventHelperClass, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddEventWindow',

        Binds: [],

        WholeDaySwitch: null,

        options: {
            title    : QUILocale.get(lg, 'calendar.window.addevent.title'),
            icon     : 'fa fa-calendar',
            maxWidth : 600,
            maxHeight: 400,
            autoclose: false,
            event    : null
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event: fired when window is opened
         */
        $onOpen: function () {
            var EventHelper = new EventHelperClass();

            var Event            = this.getAttribute('event'),
                isEventRecurring = Event && Event.recurring,
                endValue         = '';

            if (Event) {
                // DHTLMX scheduler stores recurring events' end dates different in the Event-object
                var EndDate = EventHelper.getSchedulerEventEndDate(Event);

                endValue = EventHelper.convertDateToSchedulerFormat(EndDate);
            }

            this.getContent().set({
                html: Mustache.render(template, {
                    title     : {
                        label: QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                        value: Event ? Event.text : ''
                    },
                    desc      : {
                        label: QUILocale.get(lg, 'calendar.window.addevent.event.desc'),
                        value: Event ? Event.description : ''
                    },
                    url       : {
                        label: QUILocale.get(lg, 'calendar.window.addevent.event.url'),
                        value: Event ? Event.url : ''
                    },
                    wholeDay  : QUILocale.get(lg, 'calendar.window.addevent.event.wholeDay'),
                    start     : {
                        label: QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                        value: Event ? EventHelper.convertDateToSchedulerFormat(Event.start_date) : ''
                    },
                    end       : {
                        label: QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                        value: endValue
                    },
                    recurrence: {
                        interval: {
                            label: QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.label'),
                            none : QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.none'),
                            year : QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.year'),
                            month: QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.month'),
                            week : QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.week'),
                            day  : QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.day'),
                            hour : QUILocale.get(lg, 'calendar.window.addevent.recurrence.interval.hour')
                        },
                        end     : {
                            // Scheduler uses end_date to refer to the recurrence end
                            value: isEventRecurring ? EventHelper.convertDateToSchedulerFormat(Event.end_date) : '',
                            label: QUILocale.get(lg, 'calendar.window.addevent.recurrence.end.label'),
                            none : QUILocale.get(lg, 'calendar.window.addevent.recurrence.end.none')
                        }
                    },
                    tip       : QUILocale.get(lg, 'calendar.window.addevent.tip')
                })
            });

            var popUpZIndex = this.getElm().style['z-index'];

            var EventStartInput         = this.getContent().getElementById('eventstart'),
                EventEndInput           = this.getContent().getElementById('eventend'),
                RecurrenceIntervalInput = this.getContent().getElementById('event-recurrence-interval'),
                RecurrenceEndInput      = this.getContent().getElementById('event-recurrence-end'),
                RecurrenceEndRow        = this.getContent().getElementById('event-row-recurrence-end');

            var self = this;
            require(['qui/controls/buttons/ButtonSwitch'], function (ButtonSwitch) {
                self.WholeDaySwitch = new ButtonSwitch({
                    status: false,
                    styles: {
                        width     : '100%',
                        height    : '100%',
                        background: 'none'
                    },
                    events: {
                        'change': function () {
                            if (this.getStatus()) {

                                // Is a start date already set?
                                if (EventStartInput.value) {
                                    EventStartInput.value = EventStartInput.value.split(' ')[0] + ' 00:00';
                                }

                                DatePicker.options.timePicker = false;
                                DatePicker.options.format = '%Y-%m-%d 00:00';

                                EventEndInput.disabled = true;
                                EventEndInput.parentElement.parentElement.style.display = 'none';
                            } else {
                                DatePicker.options.timePicker = true;
                                DatePicker.options.format = '%Y-%m-%d %H:%M';

                                EventEndInput.disabled = false;
                                EventEndInput.parentElement.parentElement.style.display = 'block';
                            }
                        }
                    }
                }).inject(self.getContent().getElementById('whole-day'));

                if (Event && EventHelper.isWholeDayEvent(Event)) {
                    self.WholeDaySwitch.on();
                }
            });

            // Display a date picker for the event start/end inputs
            var DatePicker = new PickerDate([EventStartInput, EventEndInput, RecurrenceEndInput], {
                timePicker: true,
                format    : '%Y-%m-%d %H:%M'
            });


            // Should be hidden by default, doing it via the HTML file gives the date-input the wrong size
            RecurrenceEndRow.hide();

            // When a recurrence interval is selected, show the recurrence end input, otherwise hide it
            RecurrenceIntervalInput.addEvent('change', function () {
                if (RecurrenceIntervalInput.value !== 'none') {
                    RecurrenceEndRow.show();
                } else {
                    RecurrenceEndInput.value = '';
                    RecurrenceEndRow.hide();
                }
            });

            if (isEventRecurring) {
                RecurrenceIntervalInput.value = EventHelper.convertRecurrencePatternToInterval(Event.rec_pattern);
                RecurrenceEndRow.show();
            }

            // Place the date picker in front of the currently open AddEvent Popup
            DatePicker.picker.style['z-index'] = popUpZIndex + 1;
        },

        getValues: function () {
            var Content = this.getContent();

            var values = {
                text       : Content.getElement('[name=eventtitle]').value,
                description: Content.getElement('[name=eventdesc]').value,
                url        : Content.getElement('[name=eventurl]').value,
                isWholeDay : this.WholeDaySwitch.getStatus(),
                StartDate  : new Date(Content.getElement('[name=eventstart]').value),
                EndDate    : new Date(Content.getElement('[name=eventend]').value)
            };

            if (values.isWholeDay) {
                values.StartDate.setHours(0, 0, 0, 0);

                values.EndDate.setHours(0, 0, 0, 0);
                values.EndDate.setDate(values.StartDate.getDate() + 1);
            }

            // If recurrence interval, set it to the result
            var recurrenceIntervalValue    = null,
                recurrenceIntervalRawValue = Content.getElement('[name=event-recurrence-interval]').value;

            if (recurrenceIntervalRawValue && recurrenceIntervalRawValue != 'none') {
                recurrenceIntervalValue = recurrenceIntervalRawValue;
            }

            values.recurrenceInterval = recurrenceIntervalValue;

            // If recurrence end, create Date-object from the value and set it to the result
            var RecurrenceEndValue    = null,
                recurrenceEndRawValue = Content.getElement('[name=event-recurrence-end]').value;

            if (recurrenceEndRawValue) {
                RecurrenceEndValue = new Date(recurrenceEndRawValue);
            }

            values.RecurrenceEndDate = RecurrenceEndValue;

            return values;
        },

        getEventForSchedulerFromValues: function () {
            var Event = this.getAttribute('event') || {};
            var Content = this.getContent();

            var isEventRecurring = Content.getElement('[name=event-recurrence-interval]').value != 'none';
            var isWholeDay = this.WholeDaySwitch.getStatus();

            var StartInputDate = new Date(Content.getElement('[name=eventstart]').value);
            var EndInputDate = new Date(Content.getElement('[name=eventend]').value);

            if (isWholeDay) {
                StartInputDate.setHours(0, 0, 0, 0);

                EndInputDate.setHours(0, 0, 0, 0);
                EndInputDate.setDate(StartInputDate.getDate() + 1);
            }

            Event.description = Content.getElement('[name=eventdesc]').value;
            Event.text = Content.getElement('[name=eventtitle]').value;
            Event.url = Content.getElement('[name=eventurl]').value;
            Event.start_date = StartInputDate;

            if (!isEventRecurring) {
                delete Event.event_length;
                delete Event.event_pid;
                delete Event.rec_pattern;
                delete Event.rec_type;

                Event.recurring = false;
                Event.end_date = EndInputDate;
            } else {
                Event.recurring = true;
                Event.event_pid = 0;
                Event.event_length = (EndInputDate.getTime() - StartInputDate.getTime()) / 1000;

                Event.rec_pattern = Content.getElement('[name=event-recurrence-interval]').value + '_1___';
                Event.rec_type = Event.rec_pattern;

                var RecurrenceEnd = Content.getElement('[name=event-recurrence-end]').value;
                if (RecurrenceEnd) {
                    Event.end_date = new Date(RecurrenceEnd);
                }
            }

            return Event;
        }
    });
});