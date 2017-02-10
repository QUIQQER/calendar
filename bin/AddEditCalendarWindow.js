/**
 * Displays a dialog to edit or add a calendar.
 *
 * @module 'package/quiqqer/calendar/bin/AddEditCalendarWindow'
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI'
 * @require 'qui/controls/windows/Confirm'
 * @require 'package/quiqqer/calendar/bin/Calendars',
 * @require 'Locale'
 * @require 'Mustache'
 * @require 'text!package/quiqqer/calendar/bin/AddEditCalendarWindow.html'
 * @require 'css!package/quiqqer/calendar/bin/AddEditCalendarWindow.css'
 */
define('package/quiqqer/calendar/bin/AddEditCalendarWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/calendar/bin/Calendars',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddEditCalendarWindow.html',
    'css!package/quiqqer/calendar/bin/AddEditCalendarWindow.css'

], function (QUI, QUIConfirm, Calendars, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddEditCalendarWindow',

        Binds: [
            'submit',
            'open',
            'initialize'
        ],

        options: {
            icon     : 'fa fa-calendar',
            calendar : null,
            maxWidth : 450,
            maxHeight: 300,
            autoclose: false
        },

        /**
         * event: fired when the window is opened
         */
        open: function ()
        {
            this.parent();

            var calendar = this.getAttribute('calendar');

            var data = {
                calendar_title   : QUILocale.get(lg, 'calendar.title'),
                calendar_isPublic: QUILocale.get(lg, 'calendar.is_public')
            };

            if (calendar !== null) {
                data.name     = calendar.name;
                data.isPublic = calendar.isPublic;
            }

            this.getContent().set({
                html: Mustache.render(template, data)
            });
        },

        /**
         * event: fired when the window (form) is submitted
         */
        submit: function (values)
        {
            var Content = this.getContent();

            var calendarName = Content.getElement('[name=calendarname]').value;
            var userid = USER.id;
            var isPublic = Content.getElement('[name=isPublic]').checked;

            this.Loader.show();

            // Do we edit or create a calendar?
            if (this.getAttribute('calendar')) {
                // Editing a calendar
                var calender = this.getAttribute('calendar');
                Calendars.editCalendar(calender.id, calendarName, isPublic).then(function (result)
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            } else {
                // Creating a calendar
                Calendars.addCalendar(userid, calendarName, isPublic).then(function ()
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            }
        }
    });
});