/**
 * @module package/quiqqer/calendar/bin/Panel
 */
define('package/quiqqer/calendar/bin/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'controls/grid/Grid'

], function (QUI, QUIPanel, QUIConfirm, QUIAjax, QUILocale, Grid)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/Panel',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onResize',
            '$onButtonAddEventClick',
            '$onButtonAddCalendarClick',
            'deleteMarkedCalendars'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.setAttributes({
                'icon': 'fa fa-calendar'
            });

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function ()
        {
            var self = this;
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


            this.addButton({
                textimage: 'fa fa-trash',
                events   : {
                    onClick: this.deleteMarkedCalendars
                }
            });

            var Content   = this.getContent(),

                Container = new Element('div', {
                    'class': 'box',
                    styles : {
                        width : '100%',
                        height: '100%'
                    }
                }).inject(Content);


            this.$Grid = new Grid(Container, {
                columnModel      : [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'string',
                    width    : 50
                }, {
                    header   : QUILocale.get(lg, 'calendar.title'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'calendar.userid'),
                    dataIndex: 'userid',
                    dataType : 'string',
                    width    : 75
                }, {
                    header   : QUILocale.get(lg, 'calendar.isglobal'),
                    dataIndex: 'isglobal',
                    dataType : 'boolean',
                    width    : 50
                }],
                multipleSelection: true,
                pagination       : true
            });

            this.$Grid.addEvents({
                onRefresh: function () {
                    self.loadCalendars();
                },

                onClick: function(data) {
                    var rowData = self.$Grid.getDataByRow(data.row);
                    self.showEvents(rowData.id);
                },

                onDblClick: function (data) {
                    var rowData = self.$Grid.getDataByRow(data.row);
                    self.editCalendar(rowData.id);
                }
            });

            this.loadCalendars();
        },

        /**
         * Load the calendars into the grid.
         */
        loadCalendars: function () {
            var self = this;

            QUIAjax.get('package_quiqqer_calendar_ajax_getCalendars', function (result) {
                if (!self.$Grid) {
                    return;
                }

                self.$Grid.setData({
                    data: result
                });
            }, {
                'package': 'quiqqer/calendar'
            });
            return this;
        },

        /**
         * Run when the Panel is inserted into the page.
         */
        $onInject: function () {},

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            var Content = this.getContent(),
                size    = Content.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * Adds a new event to a calendar
         */
        $onButtonAddEventClick: function ()
        {
            require(['package/quiqqer/calendar/bin/AddEventWindow'], function (AddEventWindow) {
                var aeWindow = new AddEventWindow();
                aeWindow.open();
            });
        },

        /**
         * Adds a new calendar.
         */
        $onButtonAddCalendarClick: function ()
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/CalendarWindow'], function (CalendarWindow) {
                var cWindow = new CalendarWindow();
                cWindow.addEvent('onClose', function() {
                    self.$Grid.refresh();
                });
                cWindow.open();
            });
        },


        /**
         * Edits the calendar with the given ID
         *
         * @param calendarID - The calendar to edit
         */
        editCalendar: function(calendarID)
        {
            var self = this;

            require(['package/quiqqer/calendar/bin/CalendarWindow'], function (CalendarWindow) {
                new CalendarWindow({
                    calendarID: calendarID,
                    events: {
                        onClose: function () {
                            self.loadCalendars();
                        }
                    }
                }).open();
            });

            return this;
        },


        /**
         * Open the delete marked calendars window and delete all marked calendars
         *
         * @return {self}
         */
        deleteMarkedCalendars: function ()
        {
            if (!this.$Grid) {
                return this;
            }

            var self = this,
                data = this.$Grid.getSelectedData();

            if (!data.length) {
                return this;
            }

            var ids = data.map(function (o) {
                return o.id;
            });

            new QUIConfirm({
                icon       : 'fa fa-remove',
                title      : QUILocale.get(lg, 'calendar.window.delete.calendar.title'),
                text       : QUILocale.get(lg, 'calendar.window.delete.calendar.text'),
                information: QUILocale.get(lg, 'calendar.window.delete.calendar.information', {
                    ids: ids.join(', ')
                }),
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        QUIAjax.post('package_quiqqer_calendar_ajax_delete', function () {
                            Win.close();
                            self.loadCalendars();
                        }, {
                            'package': 'quiqqer/calendar',
                            ids      : JSON.encode(ids)
                        });
                    }
                }
            }).open();

            return this;
        },


        /**
         * Show the events of a given calendar ID
         *
         * @param calendarID The calendar id of which the events should be shown
         */
        showEvents: function(calendarID) {
            var self = this;

            require([
                'package/quiqqer/calendar/bin/CalendarPanel',
                'utils/Panels'
            ], function (CalendarPanel, Utils) {
                Utils.openPanelInTasks( new CalendarPanel({
                    title: 'Events',
                    calendarID: calendarID
                }) );
            });

            return this;
        }
    });
});