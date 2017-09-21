/**
 * @module package/quiqqer/calendar/bin/controls/SelectItem
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/elements/SelectItem
 * @require Ajax
 */
define('package/quiqqer/calendar/bin/controls/SelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem',
    'Ajax'

], function (QUI, QUIElementSelectItem, QUIAjax)
{
    "use strict";

    return new Class({

        Extends: QUIElementSelectItem,
        Type   : 'package/quiqqer/calendar/bin/controls/SelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.setAttribute('icon', 'fa fa-calendar');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function ()
        {
            var self = this;
            return new Promise(function (resolve)
            {
                QUIAjax.get('package_quiqqer_calendar_ajax_getCalendar', function (result)
                {
                    self.$Text.set({
                        html: result.calendarname
                    });
                    resolve();
                }, {
                    'package' : 'quiqqer/calendar',
                    calendarID: this.getAttribute('id'),
                    onError: function() {self.destroy();}
                });
            }.bind(this));
        }
    });
});
