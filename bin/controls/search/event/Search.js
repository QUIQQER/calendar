/**
 *
 * @module package/quiqqer/calendar/bin/controls/search/event/Search
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 *
 * @event onLoaded
 */
define('package/quiqqer/calendar/bin/controls/search/event/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'controls/grid/Grid',

    'Ajax',
    'Locale'

], function (QUI, QUIControl, Grid, QUIAjax, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/calendar/bin/controls/search/event/Search',

        Binds: [
            '$onInject'
        ],

        options: {
            button: true
        },

        initialize: function (options)
        {
            this.parent(options);

            this.$Grid = null;

            this.addEvents({
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        create: function ()
        {
            var self = this,
                Elm  = this.parent();

            // creates grid
            this.$Grid = new Grid(Elm, {
                columnModel      : [{
                    header   : QUILocale.get(lg, 'event.title'),
                    dataIndex: 'text',
                    dataType : 'string',
                    width    : 150
                }],
                multipleSelection: false,
                pagination       : true
            });

            this.$Grid.addEvents({
                onRefresh: function ()
                {
                    self.loadEvents();
                },

                onDblClick: function ()
                {
                    // Fire event for whole control, so Window.js can notice the double click
                    self.fireEvent('onDblClick');
                }
            });

            this.loadEvents();

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function ()
        {
            var self = this;
            (function ()
            {
                self.resize();
            }).delay(100);

            this.fireEvent('loaded');
        },


        /**
         * event : on resize
         */
        $onResize: function ()
        {
            this.resize();
        },


        resize: function ()
        {
            if (!this.$Grid) {
                return;
            }

            var ParentElem = this.$Elm.getParent(),
                size       = ParentElem.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
            this.$Grid.resize();
        },


        getSelected: function ()
        {
            if (!this.$Grid) {
                return [];
            }

            return this.$Grid.getSelectedData();
        },


        /**
         * Loads the events into the grid.
         */
        loadEvents: function ()
        {
            var self = this;

            QUIAjax.get('package_quiqqer_calendar_ajax_getEvents', function (result)
            {
                if (!self.$Grid) {
                    return;
                }

                self.$Grid.setData({
                    data: result
                });
            }, {
                'package': 'quiqqer/calendar'
            });
        }
    });
});
