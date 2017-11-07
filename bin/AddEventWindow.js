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

    'Locale',
    'Mustache',

    'text!package/quiqqer/calendar/bin/AddEventWindow.html',

    'css!package/quiqqer/calendar/bin/AddEventWindow.css',
    'css!package/quiqqer/calendar-controls/bin/Source/datepicker.css'

], function (QUI, QUIConfirm, Picker, PickerAttach, PickerDate, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddEventWindow',

        Binds: [],

        options: {
            title    : QUILocale.get(lg, 'calendar.window.addevent.title'),
            icon     : 'fa fa-calendar',
            maxWidth : 600,
            maxHeight: 400,
            autoclose: false
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
            this.getContent().set({
                html: Mustache.render(template, {
                    title   : QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                    desc    : QUILocale.get(lg, 'calendar.window.addevent.event.desc'),
                    wholeDay: QUILocale.get(lg, 'calendar.window.addevent.event.wholeDay'),
                    start   : QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                    end     : QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                    tip     : QUILocale.get(lg, 'calendar.window.addevent.tip')
                })
            });
            var popUpZIndex = this.getElm().style['z-index'];

            var EventStartInput = this.getContent().getElementById('eventstart'),
                EventEndInput   = this.getContent().getElementById('eventend');

            var self = this;
            require(['qui/controls/buttons/ButtonSwitch'], function (ButtonSwitch) {
                new ButtonSwitch({
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
            });

            // Display a date picker for the event start/end inputs
            var DatePicker = new PickerDate([EventStartInput, EventEndInput], {
                timePicker: true,
                format    : '%Y-%m-%d %H:%M'
            });

            // Place the date picker in front of the currently open AddEvent Popup
            DatePicker.picker.style['z-index'] = popUpZIndex + 1;
        }
    });
});