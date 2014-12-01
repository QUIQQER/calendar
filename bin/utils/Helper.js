
/**
 * Helper utils for the calendar
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define(['Locale'], function(Locale)
{
    "use strict";

    return {

        /**
         * Return the day names
         *
         * @return {Array}
         */
        getDayList : function()
        {
            return [
                Locale.get( 'quiqqer/calendar', 'sunday' ),
                Locale.get( 'quiqqer/calendar', 'monday' ),
                Locale.get( 'quiqqer/calendar', 'tuesday' ),
                Locale.get( 'quiqqer/calendar', 'wednesday' ),
                Locale.get( 'quiqqer/calendar', 'thursday' ),
                Locale.get( 'quiqqer/calendar', 'friday' ),
                Locale.get( 'quiqqer/calendar', 'saturday' )
            ];
        },

        /**
         * Return the abbreviations of the day names
         *
         * @return {Array}
         */
        getDayListShort : function()
        {
            return [
                Locale.get( 'quiqqer/calendar', 'sunday.short' ),
                Locale.get( 'quiqqer/calendar', 'monday.short' ),
                Locale.get( 'quiqqer/calendar', 'tuesday.short' ),
                Locale.get( 'quiqqer/calendar', 'wednesday.short' ),
                Locale.get( 'quiqqer/calendar', 'thursday.short' ),
                Locale.get( 'quiqqer/calendar', 'friday.short' ),
                Locale.get( 'quiqqer/calendar', 'saturday.short' )
            ];
        },

        /**
         * Return the months
         *
         * @return {Array}
         */
        getMonthList : function()
        {
            return [
                Locale.get( 'quiqqer/calendar', 'month.01' ),
                Locale.get( 'quiqqer/calendar', 'month.02' ),
                Locale.get( 'quiqqer/calendar', 'month.03' ),
                Locale.get( 'quiqqer/calendar', 'month.04' ),
                Locale.get( 'quiqqer/calendar', 'month.05' ),
                Locale.get( 'quiqqer/calendar', 'month.06' ),
                Locale.get( 'quiqqer/calendar', 'month.07' ),
                Locale.get( 'quiqqer/calendar', 'month.08' ),
                Locale.get( 'quiqqer/calendar', 'month.09' ),
                Locale.get( 'quiqqer/calendar', 'month.10' ),
                Locale.get( 'quiqqer/calendar', 'month.11' ),
                Locale.get( 'quiqqer/calendar', 'month.12' )
            ];
        },

        /**
         * Return the month abbreviations
         *
         * @return {Array}
         */
        getMonthListShort : function()
        {
            return [
                Locale.get( 'quiqqer/calendar', 'month.01.short' ),
                Locale.get( 'quiqqer/calendar', 'month.02.short' ),
                Locale.get( 'quiqqer/calendar', 'month.03.short' ),
                Locale.get( 'quiqqer/calendar', 'month.04.short' ),
                Locale.get( 'quiqqer/calendar', 'month.05.short' ),
                Locale.get( 'quiqqer/calendar', 'month.06.short' ),
                Locale.get( 'quiqqer/calendar', 'month.07.short' ),
                Locale.get( 'quiqqer/calendar', 'month.08.short' ),
                Locale.get( 'quiqqer/calendar', 'month.09.short' ),
                Locale.get( 'quiqqer/calendar', 'month.10.short' ),
                Locale.get( 'quiqqer/calendar', 'month.11.short' ),
                Locale.get( 'quiqqer/calendar', 'month.12.short' )
            ];
        },

        /**
         * Return the short month name
         *
         * @param {Number} month - number of the month
         * @return {String}
         */
        getMonthShort : function(month)
        {
            var list = this.getMonthListShort();

            return typeof list[ month ] !== 'undefined' ? list[ month ] : '';
        },

        /**
         * Return the short month name
         *
         * @param {Number} month - number of the month
         * @return {String}
         */
        getMonth : function(month)
        {
            var list = this.getMonthList();

            return typeof list[ month ] !== 'undefined' ? list[ month ] : '';
        }
    };
});