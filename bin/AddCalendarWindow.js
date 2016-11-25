define('package/quiqqer/calendar/bin/AddCalendarWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddCalendarWindow.html',
    'css!package/quiqqer/calendar/bin/AddCalendarWindow.css'

], function (QUI, QUIConfirm, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddCalendarWindow',

        Binds: [
            '$onSubmit',
            '$onOpen'
        ],

        options: {
            title    : QUILocale.get(lg, 'calendar.window.addcalendar.title'),
            icon     : 'fa fa-calendar',
            maxWidth : 450,
            maxHeight: 300,
            autoclose: false
        },

        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        $onOpen: function ()
        {
            var Content = this.getContent();

            Content.set({
                html: Mustache.render(template, {
                    test: 1
                })
            });
        },

        /**
         * event: on submit event
         */
        $onSubmit: function (values)
        {
            var Content = this.getContent();

            var calendarName = Content.getElement('[name=calendarname]').value;
            var userid = null;

            if(!Content.getElement('[name=isGlobal]').checked) {
                userid = USER.id;
            }

            this.Loader.show();
            this.createCalendar(calendarName, userid).then(function ()
            {
                this.close();
            }.bind(this)).catch(function ()
            {
                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Create a new calendar
         *
         * @returns {Promise}
         */
        createCalendar: function (calendarName, userid)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_createCalendar', resolve, {
                    'package': 'quiqqer/calendar',
                    'userid' : userid,
                    'name'   : calendarName,
                    onError  : reject
                });
            });
        }
    });
});