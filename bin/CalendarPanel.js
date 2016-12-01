/**
 * @module package/quiqqer/calendar/bin/CalendarPanel
 */
define('package/quiqqer/calendar/bin/CalendarPanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'package/quiqqer/calendar-controls/bin/Scheduler',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/CalendarPanel.html',
    'css!package/quiqqer/calendar-controls/bin/htmlxScheduler/dhtmlxscheduler.css'

], function (QUI, QUIPanel, Scheduler, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends  : QUIPanel,
        Type     : 'package/quiqqer/calendar/bin/CalendarPanel',

        calendarID: null,

        Binds: [
            '$onCreate',
            '$onResize',
            '$onInject'
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
            console.log('CID: ' + this.calendarID);

            var Content = this.getContent();
            Content.set({
                html: Mustache.render(template)
            });

            Scheduler.init(Content.getElementById('scheduler_here'));

            QUIAjax.get('package_quiqqer_calendar_ajax_getCalendarAsIcal', function (result)
            {
                console.log(result);
                Scheduler.config.full_day = true;
                Scheduler.parse(result, 'ical');
            }, {
                'package' : 'quiqqer/calendar',
                calendarID: this.calendarID
            });
        },

        /**
         * event : on resize
         */
        $onResize: function ()
        {
            this.updateSchedularView()

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