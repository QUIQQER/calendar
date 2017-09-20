/**
 * Helper class for color operations.
 *
 * @module package/quiqqer/calendar/bin/classes/ColorHelper
 * @author www.pcsg.de (Jan Wennrich)
 *
 */
define('package/quiqqer/calendar/bin/classes/ColorHelper', [], function () {
    "use strict";

    return new Class({

        Type: 'package/quiqqer/calendar/bin/classes/ColorHelper',


        /**
         * Converts a hex color (#0033FF, #03F, 03F, 0033FF) to an Object with RGB values.
         * @see Taken from Tim Down on {@link https://stackoverflow.com/a/5624139|StackOverflow}
         *
         * @param hex - Hex color format: '#0033FF', '#03F', '03F', or '0033FF'
         *
         * @return {Object|null} - RGB colors in object properties r, g, and b; Returns null on error
         */
        hexToRGB: function (hex) {
            // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
            var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
            hex = hex.replace(shorthandRegex, function (m, r, g, b) {
                return r + r + g + g + b + b;
            });

            var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },


        /**
         * Converts an object with properties r, g, b to an hex string (e.g. "#0033FF")
         * @see Taken from Tim Down on {@link https://stackoverflow.com/a/13070198|StackOverflow}
         *
         * @param {object} rgb - object with properties r, g, b
         *
         * @return {string} hex string (e.g. "#0033FF")
         */
        rgbToHex: function (rgb) {
            var rgbArray = [rgb.r, rgb.g, rgb.b];

            var hexString = "#";
            rgbArray.forEach(function (value) {

                //Convert to a base16 string
                var hexValue = parseInt(value).toString(16);

                //Add zero if we get only one character
                if (hexValue.length === 1) {
                    hexValue = "0" + hexValue;
                }

                hexString += hexValue;
            });
            return hexString;
        },


        /**
         * Calculates whether the text color for the given background color should be black or white.
         * @see Modified version of Marcus Mangelsdorf reply on {@link https://stackoverflow.com/a/36888120|StackOverflow}
         *
         * @param {Object} bgColor - Object with properties r, g and b
         *
         * @return {Object|null} - RGB colors in object properties r, g, and b; Returns null on error
         */
        getTextColorForBackgroundColor: function (bgColor) {
            //  Counting the perceptive luminance (aka luma) - human eye favors green color...
            var luminance = (((0.299 * bgColor.r) + ((0.587 * bgColor.g) + (0.114 * bgColor.b))) / 255);

            // Return black for bright colors, white for dark colors
            var r, g, b;
            r = g = b = 0;

            if (luminance < 0.5) {
                r = g = b = 255;
            }

            return {r: r, g: g, b: b};
        },


        /**
         * Returns the text color for the Scheduler
         *
         * @param {string} hexColor - Hex color format: '#0033FF', '#03F', '03F', or '0033FF'
         *
         * @return {string} Hex string format #0033FF
         */
        getSchedulerTextColor: function (hexColor) {
            var rgb = this.hexToRGB(hexColor);
            var textColor = this.getTextColorForBackgroundColor(rgb);

            return this.rgbToHex(textColor);
        }

    });
});
