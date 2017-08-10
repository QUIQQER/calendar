/**
 * @module package/quiqqer/calendar/bin/controls/eventSelect/EventSelectItem
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/elements/SelectItem
 * @require Ajax
 */
define('package/quiqqer/calendar/bin/controls/eventSelect/EventSelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem',
    'Ajax'

], function (QUI, QUIElementSelectItem, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIElementSelectItem,
        Type   : 'package/quiqqer/calendar/bin/controls/eventSelect/EventSelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);

            this.setAttribute('icon', 'fa fa-clock-o');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function () {
            return new Promise(function (resolve) {
                QUIAjax.get('package_quiqqer_calendar_ajax_getEvent', function (result) {

                    this.$Text.set({
                        html: result.text
                    });

                    resolve();

                }.bind(this), {
                    'package': 'quiqqer/calendar',
                    eventID  : this.getAttribute('id')
                });
            }.bind(this));
        }
    });
});
