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
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddEventWindow.html',
    'css!package/quiqqer/calendar/bin/AddEventWindow.css'

], function (QUI, QUIConfirm, QUILocale, Mustache, template)
{
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

        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event: fired when window is opened
         */
        $onOpen: function ()
        {
            this.getContent().set({
                html: Mustache.render(template, {
                    title: QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                    desc : QUILocale.get(lg, 'calendar.window.addevent.event.desc'),
                    start: QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                    end  : QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                    tip  : QUILocale.get(lg, 'calendar.window.addevent.tip')
                })
            });
            var popUpZIndex = this.getElm().style['z-index'];

            var StartInput = this.getContent().getElementById('eventstart');
            var EndInput = this.getContent().getElementById('eventend');

            require([
                'package/quiqqer/calendar-controls/bin/Source/Picker',
                'package/quiqqer/calendar-controls/bin/Source/Picker.Attach',
                'package/quiqqer/calendar-controls/bin/Source/Picker.Date',
                'css!package/quiqqer/calendar-controls/bin/Source/datepicker.css'
            ], function(Picker, PickerAttach, PickerDate) {
                var DatePicker = new PickerDate([StartInput, EndInput], {
                    timePicker: true,
                    format: '%d-%m-%Y %H:%M'
                });

                DatePicker.picker.style['z-index'] = popUpZIndex + 1;
            });
        }
    });
});