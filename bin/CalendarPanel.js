/**
 * @module package/quiqqer/calendar/bin/CalendarPanel
 */
define('package/quiqqer/calendar/bin/CalendarPanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/utils/Functions',
    'package/quiqqer/calendar/bin/Calendars',
    'package/quiqqer/calendar-controls/bin/Scheduler',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/calendar/bin/CalendarPanel.html',
    'css!package/quiqqer/calendar-controls/bin/htmlxScheduler/dhtmlxscheduler.css'

], function (QUI, QUIPanel, QUIFunctionUtils, Calendars, Scheduler, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends  : QUIPanel,
        Type     : 'package/quiqqer/calendar/bin/CalendarPanel',

        calendarID: null,

        ChangeEvent: null,
        AddEvent: null,
        DeleteEvent: null,

        Binds: [
            '$onCreate',
            '$onResize',
            '$onInject',
            '$onClose'
        ],

        initialize: function (options)
        {
            this.parent(options);
            this.calendarID = options.calendarID;

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize,
                onInject: this.$onInject
            });
        },

        $onCreate: function ()
        {
            var self = this;
            var Content = this.getContent();

            Content.set({
                html: Mustache.render(template)
            });

            Scheduler.clearAll();
            Scheduler.init(Content.getElement('.dhx_cal_container'));

            // Parses the calendar iCal string into the scheduler
            Calendars.getCalendarAsIcal(this.calendarID).then(function(result) {
                Scheduler.parse(result, 'ical');
            });

            // Run when an event is edited in the scheduler
            this.ChangeEvent = Scheduler.attachEvent('onEventChanged', function (id, ev)
            {
                Calendars.editEvent(self.calendarID,
                    ev.id,
                    ev.text,
                    ev.description,
                    ev.start_date.getTime()/1000,
                    ev.end_date.getTime()/1000
                )
            });

            // Run when an event is added to the scheduler
            this.AddEvent = Scheduler.attachEvent('onEventAdded', function(id, ev)
            {
               Calendars.addEvent(self.calendarID,
                   ev.text,
                   ev.text,
                   ev.start_date.getTime()/1000,
                   ev.end_date.getTime()/1000
               ).then(function(result) {
                   if (result == null) {
                       return;
                   }
                   Scheduler.changeEventId(id, parseInt(result));
               });
            });

            // Run when an event is deleted from scheduler
            this.DeleteEvent = Scheduler.attachEvent('onEventDeleted', function(id)
            {
              Calendars.deleteEvent(self.calendarID, id);
            });
        },

        /**
         * event : on resize
         */
        $onResize: function ()
        {
            this.updateSchedularView()

        },


        /**
         * Removes all Events (add,change,delete) from Scheduler
         *
         * event : on destroy
         */
        $onDestroy: function()
        {
            Scheduler.detachEvent(this.AddEvent);
            Scheduler.detachEvent(this.ChangeEvent);
            Scheduler.detachEvent(this.DeleteEvent);
        },

        $onInject: function()
        {
            this.updateSchedularView()
        },

        updateSchedularView: function()
        {
            if (Scheduler) {
                Scheduler.update_view();
            }
        }
    });
});