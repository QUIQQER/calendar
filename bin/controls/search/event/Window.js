/**
 *
 * @module package/quiqqer/calendar/bin/controls/search/event/Window
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/windows/Confirm
 * @require package/quiqqer/areas/bin/classes/Handler
 * @require Locale
 * @require css!package/quiqqer/areas/bin/controls/search/Window.css
 */
define('package/quiqqer/calendar/bin/controls/search/event/Window', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'Locale'

], function (QUI, QUIControl, QUIConfirm, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/controls/search/event/Window',

        Binds: [
            '$onOpen'
        ],

        options: {
            maxHeight: 600,
            maxWidth : 800,
            icon     : 'fa fa-clock-o',
            title    : QUILocale.get(lg, 'calendar.control.eventSearch.title'),
            autoclose: false,

            cancel_button: {
                text     : QUILocale.get('quiqqer/system', 'cancel'),
                textimage: 'fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get('quiqqer/system', 'add'),
                textimage: 'fa fa-plus'
            }
        },

        initialize: function (options)
        {
            this.parent(options);

            this.$Search = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onResize: this.$onResize
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @returns {HTMLDivElement}
         */
        $onOpen: function (Win)
        {
            var self    = this,
                Content = Win.getContent();

            Win.Loader.show();

            Content.set('html', '');
            Content.addClass('event-search');

            require([
                'package/quiqqer/calendar/bin/controls/search/event/Search'
            ], function (Search)
            {
                self.$Search = new Search({
                    multiple: false
                }).inject(self.getContent());

                // If item of Search is double clicked submit this window
                self.$Search.addEvent('onDblClick', self.submit.bind(self));
            });

            Win.Loader.hide();
        },


        $onResize: function ()
        {
            if (!this.$Search) {
                return;
            }

            this.$Search.resize();
        },


        /**
         * Submit
         */
        submit: function ()
        {
            this.fireEvent('submit', [this, this.$Search.getSelected()]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
