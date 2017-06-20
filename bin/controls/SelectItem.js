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
            return new Promise(function (resolve)
            {
                QUIAjax.get('package_quiqqer_calendar_ajax_getCalendar', function (result)
                {
                    this.$Text.set({
                        html: result.calendarname
                    });

                    resolve();

                }.bind(this), {
                    'package' : 'quiqqer/calendar',
                    calendarID: this.getAttribute('id')
                });
            }.bind(this));
        }
    });
});
