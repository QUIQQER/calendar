/**
 * Datepicker for QUIQQER
 *
 * @module URL_OPT_DIR/quiqqer/calendar/bin/Calendar
 *
 * @author Arian Stolwijk (MooTools Datepicker)
 * @author www.pcsg.de (Henning Leutz - QUIQQER / AMD / moofx adaptation )
 */

define([

    'URL_OPT_DIR/quiqqer/calendar/bin/Source/Picker',
    'URL_OPT_DIR/quiqqer/calendar/bin/Source/Picker.Attach',
    'URL_OPT_DIR/quiqqer/calendar/bin/Source/Picker.Date',

    'css!URL_OPT_DIR/quiqqer/calendar/bin/Source/datepicker_dashboard/datepicker_dashboard.css'

], function(Picker, Attach, DatePicker)
{
    return DatePicker;
});