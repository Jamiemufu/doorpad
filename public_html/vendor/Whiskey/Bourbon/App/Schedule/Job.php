<?php


namespace Whiskey\Bourbon\App\Schedule;


use Cron\CronExpression;


/**
 * Scheduled Job template class (method names and cron logic adapted from Laravel)
 * @package Whiskey\Bourbon\App\Schedule
 */
class Job
{


    const _NO_REUSE = true;


    protected $_interval = '* * * * *';


    /**
     * Set a custom cron interval
     * @param  string $interval Cron interval string
     * @return self             Job object for chaining
     */
    protected function _cron($interval = '* * * * *')
    {

        $this->_interval = $interval;

        return $this;

    }


    /**
     * Update a specific section of the interval
     * @param  int    $section Section of interval to update (1-5)
     * @param  string $value   New value of section
     * @return self            Job object for chaining
     */
    protected function _setIntervalFragment($section = 1, $value = '*')
    {

        $section--;

        $intervals            = explode(' ', $this->_interval);
        $intervals[$section] = $value;
        $interval             = implode(' ', $intervals);

        $this->_cron($interval);

        return $this;

    }


    /**
     * Set an hourly interval
     * @return self Job object for chaining
     */
    protected function _hourly()
    {

        $this->_cron('0 * * * *');

        return $this;

    }


    /**
     * Set a daily interval
     * @return self Job object for chaining
     */
    protected function _daily()
    {

        $this->_cron('0 0 * * *');

        return $this;

    }


    /**
     * Set the time of the interval
     * @param  string $time Time (H:i format)
     * @return self         Job object for chaining
     * @see self::_dailyAt()
     */
    protected function _at($time = '00:00')
    {

        $this->_dailyAt($time);

        return $this;

    }


    /**
     * Set the time of the interval
     * @param  string $time Time (H:i format)
     * @return self         Job object for chaining
     */
    protected function _dailyAt($time = '00:00')
    {

        $segments = explode(':', $time);
        $hour     = (int)$segments[0];
        $minute   = (count($segments) == 2) ? (int)$segments[1] : '00';

        $this->_setIntervalFragment(2, $hour);
        $this->_setIntervalFragment(1, $minute);

        return $this;

    }


    /**
     * Set a twice-daily interval
     * @param  int  $first_hour  First hour at which to run
     * @param  int  $second_hour Second hour at which to run
     * @return self              Job object for chaining
     */
    protected function _twiceDaily($first_hour = 1, $second_hour = 13)
    {

        $hours = $first_hour . ',' . $second_hour;

        $this->_setIntervalFragment(1, 0);
        $this->_setIntervalFragment(2, $hours);

        return $this;

    }


    /**
     * Set a weekday-only interval
     * @return self Job object for chaining
     */
    protected function _weekdays()
    {

        $this->_setIntervalFragment(5, '1-5');

        return $this;

    }


    /**
     * Set a weekend-only interval
     * @return self Job object for chaining
     */
    protected function _weekends()
    {

        $this->_setIntervalFragment(5, '6-7');

        return $this;

    }


    /**
     * Set a Monday-only interval
     * @return self Job object for chaining
     */
    protected function _mondays()
    {

        $this->_days(1);

        return $this;

    }


    /**
     * Set a Tuesday-only interval
     * @return self Job object for chaining
     */
    protected function _tuesdays()
    {

        $this->_days(2);

        return $this;

    }


    /**
     * Set a Wednesday-only interval
     * @return self Job object for chaining
     */
    protected function _wednesdays()
    {

        $this->_days(3);

        return $this;

    }


    /**
     * Set a Thursday-only interval
     * @return self Job object for chaining
     */
    protected function _thursdays()
    {

        $this->_days(4);

        return $this;

    }


    /**
     * Set a Friday-only interval
     * @return self Job object for chaining
     */
    protected function _fridays()
    {

        $this->_days(5);

        return $this;

    }


    /**
     * Set a Saturday-only interval
     * @return self Job object for chaining
     */
    protected function _saturdays()
    {

        $this->_days(6);

        return $this;

    }


    /**
     * Set a Sunday-only interval
     * @return self Job object for chaining
     */
    protected function _sundays()
    {

        $this->_days(0);

        return $this;

    }


    /**
     * Set a weekly interval
     * @return self Job object for chaining
     */
    protected function _weekly()
    {

        $this->_cron('0 0 * * 0');

        return $this;

    }


    /**
     * Set a weekly interval with a specific time and day
     * @param  int    $day  Day of week on which to run (0-7 -- 0/7 = Sunday)
     * @param  string $time Time at which to run
     * @return self         Job object for chaining
     */
    protected function _weeklyOn($day = 0, $time = '00:00')
    {

        $this->_dailyAt($time);
        $this->_setIntervalFragment(5, $day);

        return $this;

    }


    /**
     * Set a monthly interval
     * @return self Job object for chaining
     */
    protected function _monthly()
    {

        $this->_cron('0 0 1 * *');

        return $this;

    }


    /**
     * Set a monthly interval with a specific time and day
     * @param  int    $day  Day of month on which to run
     * @param  string $time Time at which to run
     * @return self         Job object for chaining
     */
    protected function _monthlyOn($day = 1, $time = '00:00')
    {

        $this->_dailyAt($time);
        $this->_setIntervalFragment(3, $day);

        return $this;

    }


    /**
     * Set a quarterly interval
     * @return self Job object for chaining
     */
    protected function _quarterly()
    {

        $this->_cron('0 0 1 */3 *');

        return $this;

    }


    /**
     * Set an annual interval
     * @return self Job object for chaining
     */
    protected function _yearly()
    {

        $this->_cron('0 0 1 1 *');

        return $this;

    }


    /**
     * Set a per-minute interval
     * @return self Job object for chaining
     */
    protected function _everyMinute()
    {

        $this->_cron('* * * * *');

        return $this;

    }


    /**
     * Set a per-five-minute interval
     * @return self Job object for chaining
     */
    protected function _everyFiveMinutes()
    {

        $this->_cron('*/5 * * * *');

        return $this;

    }


    /**
     * Set a per-ten-minute interval
     * @return self Job object for chaining
     */
    protected function _everyTenMinutes()
    {

        $this->_cron('*/10 * * * *');

        return $this;

    }


    /**
     * Set a per-half-hour interval
     * @return self Job object for chaining
     */
    protected function _everyThirtyMinutes()
    {

        $this->_cron('0,30 * * * *');

        return $this;

    }


    /**
     * Set the day(s) of the week of the interval
     * @param  array|int $days Array of day numbers (or multiple day number arguments)
     * @return self            Job object for chaining
     */
    protected function _days($days = [])
    {

        $days = is_array($days) ? $days : func_get_args();
        $days = implode(',', $days);

        $this->_setIntervalFragment(5, $days);

        return $this;

    }


    /**
     * Check if the job is due
     * @return bool Whether the job is due
     */
    public function isDue()
    {

        return CronExpression::factory($this->_interval)->isDue();

    }


    /**
     * Action to be executed when the job is due
     */
    public function run() {}


}