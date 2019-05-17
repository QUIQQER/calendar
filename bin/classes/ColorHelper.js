/**
 * Helper class for color operations.
 *
 * @module package/quiqqer/calendar/bin/classes/ColorHelper
 * @author www.pcsg.de (Jan Wennrich)
 *
 */
define('package/quiqqer/calendar/bin/classes/ColorHelper', [
    'utils/Color'
], function (ColorUtil) {
    "use strict";

    return new Class({

        Type: 'package/quiqqer/calendar/bin/classes/ColorHelper',

        /**
         * Returns the text color for the Scheduler
         *
         * @param {string} hexColor - Hex color format: '#0033FF', '#03F', '03F', or '0033FF'
         *
         * @return {string} Hex string format #0033FF
         */
        getSchedulerTextColor: function (hexColor) {
            var RgbColor = ColorUtil.getRgbColorFromHex(hexColor);
            var RgbTextColor = ColorUtil.getTextColorForRgbObject(RgbColor);

            return ColorUtil.getHexFromRgbObject(RgbTextColor);
        }
    });
});
