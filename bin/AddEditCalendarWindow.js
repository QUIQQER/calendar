define('package/quiqqer/calendar/bin/AddEditCalendarWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'package/quiqqer/calendar/bin/Calendars',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddEditCalendarWindow.html',
    'css!package/quiqqer/calendar/bin/AddEditCalendarWindow.css'

], function (QUI, QUIConfirm, Calendars, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends  : QUIConfirm,
        Type     : 'package/quiqqer/calendar/bin/AddEditCalendarWindow',

        Binds: [
            'submit',
            'open',
            'initialize'
        ],

        options: {
            title      : QUILocale.get(lg, 'calendar.window.addcalendar.title'),
            icon       : 'fa fa-calendar',
            calendar   : null,
            maxWidth   : 450,
            maxHeight  : 300,
            autoclose  : false
        },

        open: function ()
        {
            this.parent();

            var calendar = this.getAttribute('calendar');
            var data = {};

            if(calendar != null) {
                data = {
                    name: calendar.name,
                    isGlobal: calendar.isglobal
                }
            }

            this.getContent().set({
                html: Mustache.render(template, data)
            });
        },

        /**
         * event: on submit event
         */
        submit: function (values)
        {
            var Content = this.getContent();

            var calendarName = Content.getElement('[name=calendarname]').value;
            var userid = null;

            if(!Content.getElement('[name=isGlobal]').checked) {
                userid = USER.id;
            }

            this.Loader.show();

            if(this.getAttribute('calendar')) {
                var calender = this.getAttribute('calendar');
                this.editCalendar(calender.id, calendarName, userid).then(function ()
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            } else {
                this.createCalendar(calendarName, userid).then(function ()
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            }
        },

        /**
         * Create a new calendar
         *
         * @returns {Promise}
         */
        createCalendar: function (calendarName, userid)
        {
            return Calendars.addCalendar(userid, calendarName);
        },


        /**
         * Edits a calendar
         *
         * @param calendarID
         * @param calendarName
         * @param userid
         */
        editCalendar: function(calendarID, calendarName, userid)
        {
            return Calendars.editCalendar(calendarID, userid, calendarName);
//                QUIAjax.post('package_quiqqer_calendar_ajax_editCalendar', resolve, {
//                    'package'    : 'quiqqer/calendar',
//                    'calendarID' : calendarID,
//                    'userid'     : userid,
//                    'name'       : calendarName,
//                    onError      : reject
//                });
        }
    });
});