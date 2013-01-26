/**
 * Datepicker for QUIQQER
 *
 * @author Arian Stolwijk (MooTools Datepicker)
 * @author www.pcsg.de (Henning Leutz - QUIQQER / AMD adaptation )
 *
 * @module package/quiqqer/calendar/bin/Calendar
 */

define('package/quiqqer/calendar/bin/Calendar', [

    'package/quiqqer/calendar/bin/Source/Picker',
    'package/quiqqer/calendar/bin/Source/Picker.Attach',
    'package/quiqqer/calendar/bin/Source/Picker.Date',

    'css!package/quiqqer/calendar/bin/Source/datepicker.css'

], function(Picker, Attach, DatePicker)
{
    return DatePicker;
});