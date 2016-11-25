define('package/quiqqer/calendar/bin/AddEventWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddEventWindow.html',
    'css!package/quiqqer/calendar/bin/AddEventWindow.css'

], function (QUI, QUIConfirm, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddEventWindow',

        Binds: [
            '$onSubmit',
            '$onOpen'
        ],

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
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        $onOpen: function ()
        {
            var Content = this.getContent();

            Content.set({
                html: Mustache.render(template, {
                    title     : QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                    desc      : QUILocale.get(lg, 'calendar.window.addevent.event.desc'),
                    start     : QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                    end       : QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                    calendarID: QUILocale.get(lg, 'calendar.window.addevent.event.calendarid')
                })
            });
        },

        /**
         * event: on submit event
         */
        $onSubmit: function (values)
        {
            var Content = this.getContent();

            var title = Content.getElement('[name=eventtitle]').value;
            var desc = Content.getElement('[name=eventdesc]').value;
            var start = Content.getElement('[name=eventstart]').value;
            var end = Content.getElement('[name=eventend]').value;
            var calendarID = Content.getElement('[name=calendarid]').value;

            this.Loader.show();
            this.addCalendarEvent(title, desc, start, end, calendarID).then(function ()
            {
                this.close();
            }.bind(this)).catch(function ()
            {
                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Adds an event to a calendar
         *
         * @returns {Promise}
         */
        addCalendarEvent: function (title, desc, start, end, calendarID)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_addEvent', function(result) {
                    console.log(result);
                    resolve();
                }, {
                    'package'   : 'quiqqer/calendar',
                    'title'     : title,
                    'desc'      : desc,
                    'start'     : start,
                    'end'       : end,
                    'calendarid': calendarID,
                    onError     : reject
                });
            });
        }
    });
});