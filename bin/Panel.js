/**
 * @module package/quiqqer/calendar/bin/Panel
 */
define('package/quiqqer/calendar/bin/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Ajax',
    'Locale'

], function (QUI, QUIPanel, QUIAjax, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/Panel',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onButtonAddEventClick',
            '$onButtonAddCalendarClick'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.setAttributes({
                'icon': 'fa fa-calendar'
            });

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        $onCreate: function ()
        {
            this.addButton({
                text     : QUILocale.get(lg, 'panel.button.add.event.text'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.$onButtonAddEventClick
                }
            });

            this.addButton({
                text     : QUILocale.get(lg, 'panel.button.add.calendar.text'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.$onButtonAddCalendarClick
                }
            });
        },

        /**
         * Run when the Panel is inserted into the page.
         */
        $onInject: function ()
        {
//            QUIAjax.get('package_quiqqer_calendar_ajax_getList', function (result)
//            {
//                console.info(result);
//            }, {
//                'package': 'quiqqer/calendar'
//            });
        },

        /**
         * Adds a new event to a calendar
         */
        $onButtonAddEventClick: function ()
        {
//            QUIAjax.post('package_quiqqer_calendar_ajax_createCalendar', function(result) {
//                console.info(result);
//            }, {
//                'package' : 'quiqqer/calendar',
//                'userid' : 0,
//                'name' : 'Test'
//            })
        },

        /**
         * Adds a new calendar.
         */
        $onButtonAddCalendarClick: function ()
        {
            require(['package/quiqqer/calendar/bin/AddCalendarWindow'], function (AddCalendarWindow) {
                var acWindow = new AddCalendarWindow();
                acWindow.open();
            });
        }
    });
});