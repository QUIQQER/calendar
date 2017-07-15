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

        ColorPicker: null,

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
            var isExternal = this.getAttribute('isExternal');

            var data = {
                calendar_title     : QUILocale.get(lg, 'calendar.title'),
                calendar_color     : QUILocale.get(lg, 'calendar.color'),
                calendar_isPublic  : QUILocale.get(lg, 'calendar.is_public'),
                calendar_isExternal: QUILocale.get(lg, 'calendar.external_url')
            };

            if (calendar !== null) {
                data.name = calendar.name;
                data.isPublic = calendar.isPublic;
                data.isExternal = calendar.isExternal;
                data.externalUrl = calendar.externalUrl;
            }

            if(isExternal !== null) {
                data.isExternal = isExternal;
            }

            this.getContent().set({
                html: Mustache.render(template, data)
            });

            var self = this;
            require(['qui/controls/elements/ColorPicker'], function (ColorPicker)
            {
                var color = '#2F8FC6';
                if(calendar !== null && calendar.color !== null) {
                    color = calendar.color;
                }

                self.ColorPicker = new ColorPicker({
                    defaultcolor: color
                });

                self.ColorPicker.setStyle('width', '100%');
                self.ColorPicker.reset(); // Fix to show the default color
                self.getContent().getElementById('calendar-color-picker').appendChild(self.ColorPicker.getElm());
            });
        },

        /**
         * event: fired when the window (form) is submitted
         */
        submit: function ()
        {
            var Content = this.getContent();

            var calendarName = Content.getElement('[name=calendarname]').value,
                userid = USER.id,
                isPublic = Content.getElement('[name=isPublic]').checked,
                color = this.ColorPicker.getValue();

            this.Loader.show();

            // Do we edit or create a calendar?
            if (this.getAttribute('calendar')) {
                // Editing a calendar
                var calendar = this.getAttribute('calendar');

                var wasPromiseRejected = false;

                var promises = [
                    Calendars.editCalendar(calendar.id, calendarName, isPublic, color).catch(function ()
                    {
                        wasPromiseRejected = true;
                    })
                ];

                if (calendar.isExternal) {
                    promises.append(
                        Calendars.setExternalUrl(
                            calendar.id,
                            Content.getElement('[name=external_url]').value
                        ).catch(function ()
                        {
                            wasPromiseRejected = true;
                        })
                    );
                }

                Promise.all(promises).then(function ()
                {
                    this.Loader.hide();
                    if (!wasPromiseRejected) {
                        this.close();
                    }
                }.bind(this));
            } else {

                // Create internal or external calendar
                var AddCalendarPromise;
                if(this.getAttribute('isExternal')) {
                    var url = Content.getElement('[name=external_url]').value;
                    AddCalendarPromise = Calendars.addExternalCalendar(url, color);
                } else {
                    AddCalendarPromise = Calendars.addCalendar(userid, calendarName, isPublic, color);
                }

                // After Creating the calendar
                AddCalendarPromise.then(function ()
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