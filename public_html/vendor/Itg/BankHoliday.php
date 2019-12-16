<?php


namespace Itg;


/**
 * BankHoliday class
 * @package Itg
 */
class BankHoliday
{


    /**
     * Get a list of all bank holidays in a given year
     * @param  int   $year Year to retrieve bank holidays for
     * @return array       Array of bank holidays
     */
    protected static function _getAll($year = 2000)
    {

        $bank_holiday_list = [];

        /*
         * New Year's Day
         */
        switch (date('w', strtotime($year . '-01-01 12:00:00')))
        {

            case 6:
                $bank_holiday_list['New Year\'s Day'][] = $year . '-01-03';
                break;

            case 0:
                $bank_holiday_list['New Year\'s Day'][] = $year . '-01-02';
                break;

            default:
                $bank_holiday_list['New Year\'s Day'][] = $year . '-01-01';

        }

        /*
         * Good Friday
         */
        $bank_holiday_list['Good Friday'][] = date('Y-m-d', strtotime('+' . (easter_days($year) - 2) . ' days', strtotime($year . '-03-21 12:00:00')));

        /*
         * Easter Monday
         */
        $bank_holiday_list['Easter Monday'][] = date('Y-m-d', strtotime('+' . (easter_days($year) + 1) . ' days', strtotime($year . '-03-21 12:00:00')));

        /*
         * VE Day
         */
        if ($year == 1995)
        {
            $bank_holiday_list['VE Day'][] = '1995-05-08';
        }

        /*
         * May Day
         */
        else
        {

            switch (date('w', strtotime($year . '-05-01 12:00:00')))
            {

                case 0:
                    $bank_holiday_list['May Day'][] = $year . '-05-02';
                    break;

                case 1:
                    $bank_holiday_list['May Day'][] = $year . '-05-01';
                    break;

                case 2:
                    $bank_holiday_list['May Day'][] = $year . '-05-07';
                    break;

                case 3:
                    $bank_holiday_list['May Day'][] = $year . '-05-06';
                    break;

                case 4:
                    $bank_holiday_list['May Day'][] = $year . '-05-05';
                    break;

                case 5:
                    $bank_holiday_list['May Day'][] = $year . '-05-04';
                    break;

                case 6:
                    $bank_holiday_list['May Day'][] = $year . '-05-03';
                    break;

            }

        }

        /*
         * Whitsun
         */
        if ($year == 2002)
        {

            /*
             * Exception year
             */
            $bank_holiday_list['Whitsun'][] = '2002-06-03';
            $bank_holiday_list['Whitsun'][] = '2002-06-04';

        }

        else if ($year == 2012)
        {

            /*
             * Queen's Diamond Jubilee exception
             */
            $bank_holiday_list['Whitsun'][] = '2012-06-04';

        }

        else
        {

            switch (date('w', strtotime($year . '-05-31 12:00:00')))
            {

                case 0:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-25';
                    break;

                case 1:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-31';
                    break;

                case 2:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-30';
                    break;

                case 3:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-29';
                    break;

                case 4:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-28';
                    break;

                case 5:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-27';
                    break;

                case 6:
                    $bank_holiday_list['Whitsun'][] = $year . '-05-26';
                    break;

            }

        }

        /*
         * Summer Bank Holiday
         */
        switch (date('w', strtotime($year . '-08-31 12:00:00')))
        {

            case 0:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-25';
                break;

            case 1:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-31';
                break;

            case 2:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-30';
                break;

            case 3:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-29';
                break;

            case 4:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-28';
                break;

            case 5:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-27';
                break;

            case 6:
                $bank_holiday_list['Summer Bank Holiday'][] = $year . '-08-26';
                break;

        }

        /*
         * Christmas
         */
        switch (date('w', strtotime($year . '-12-25 12:00:00')))
        {

            case 5:
                $bank_holiday_list['Christmas Day'][] = $year . '-12-25';
                $bank_holiday_list['Boxing Day'][]    = $year . '-12-28';
                break;

            case 6:
                $bank_holiday_list['Christmas Day'][] = $year . '-12-27';
                $bank_holiday_list['Boxing Day'][]    = $year . '-12-28';
                break;

            case 0:
                $bank_holiday_list['Christmas Day'][] = $year . '-12-26';
                $bank_holiday_list['Boxing Day'][]    = $year . '-12-27';
                break;

            default:
                $bank_holiday_list['Christmas Day'][] = $year . '-12-25';
                $bank_holiday_list['Boxing Day'][]    = $year . '-12-26';

        }

        /*
         * Millenium Eve
         */
        if ($year == 1999)
        {
            $bank_holiday_list['Millenium Eve'][] = '1999-12-31';
        }

        /*
         * Prince William's Wedding
         */
        if ($year == 2011)
        {
            $bank_holiday_list['Prince William\'s Wedding'][] = '2011-04-29';
        }

        /*
         * Queen's Diamond Jubilee
         */
        if ($year == 2012)
        {
            $bank_holiday_list['Queen\'s Diamond Jubilee'][] = '2012-06-05';
        }

        return $bank_holiday_list;

    }


    /**
     * Check to see whether a given date is a bank holiday
     * @param  int         $day   Date day
     * @param  int         $month Date month
     * @param  int         $year  Date year
     * @return string|bool        Bank holiday name if match or FALSE if no match
     */
    public static function check($day = 0, $month = 0, $year = 0)
    {

        $bank_holidays = self::_getAll($year);
        $check_date    = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);

        foreach ($bank_holidays as $bank_holiday_name => $bank_holiday_date)
        {

            if (array_search($check_date, $bank_holiday_date) > -1)
            {
                return $bank_holiday_name;
            }

        }

        return false;

    }


}