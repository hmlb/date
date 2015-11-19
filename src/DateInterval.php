<?php

namespace HMLB\Date;

use DateInterval as BaseDateInterval;
use InvalidArgumentException;
use src\Translation\DateLocalizationCapabilities;

/**
 * A simple API extension for DateInterval.
 * The implemenation provides helpers to handle weeks but only days are saved.
 * Weeks are calculated based on the total days of the current instance.
 *
 * @property int          $years            Total years of the current interval.
 * @property int          $months           Total months of the current interval.
 * @property int          $weeks            Total weeks of the current interval calculated from the days.
 * @property int          $dayz             Total days of the current interval (weeks * 7 + days).
 * @property int          $hours            Total hours of the current interval.
 * @property int          $minutes          Total minutes of the current interval.
 * @property int          $seconds          Total seconds of the current interval.
 *
 * @property-read integer $dayzExcludeWeeks Total days remaining in the final week of the current instance (days % 7).
 * @property-read integer $daysExcludeWeeks alias of dayzExcludeWeeks
 *
 * @method static years() years($years = 1) Set the years portion of the current interval.
 * @method static year() year($years = 1) Alias for years().
 * @method static months() months($months = 1) Set the months portion of the current interval.
 * @method static month() month($months = 1) Alias for months().
 * @method static weeks() weeks($weeks = 1) Set the weeks portion of the current interval.  Will overwrite dayz value.
 * @method static week() week($weeks = 1) Alias for weeks().
 * @method static days() days($days = 1) Set the days portion of the current interval.
 * @method static dayz() dayz($days = 1) Alias for days().
 * @method static day() day($days = 1) Alias for days().
 * @method static hours() hours($hours = 1) Set the hours portion of the current interval.
 * @method static hour() hour($hours = 1) Alias for hours().
 * @method static minutes() minutes($minutes = 1) Set the minutes portion of the current interval.
 * @method static minute() minute($minutes = 1) Alias for minutes().
 * @method static seconds() seconds($seconds = 1) Set the seconds portion of the current interval.
 * @method static second() second($seconds = 1) Alias for seconds().
 * @method DateInterval years() years($years = 1) Set the years portion of the current interval.
 * @method DateInterval year() year($years = 1) Alias for years().
 * @method DateInterval months() months($months = 1) Set the months portion of the current interval.
 * @method DateInterval month() month($months = 1) Alias for months().
 * @method DateInterval weeks() weeks($weeks = 1) Set the weeks portion of the current interval.  Will overwrite dayz
 *         value.
 * @method DateInterval week() week($weeks = 1) Alias for weeks().
 * @method DateInterval days() days($days = 1) Set the days portion of the current interval.
 * @method DateInterval dayz() dayz($days = 1) Alias for days().
 * @method DateInterval day() day($days = 1) Alias for days().
 * @method DateInterval hours() hours($hours = 1) Set the hours portion of the current interval.
 * @method DateInterval hour() hour($hours = 1) Alias for hours().
 * @method DateInterval minutes() minutes($minutes = 1) Set the minutes portion of the current interval.
 * @method DateInterval minute() minute($minutes = 1) Alias for minutes().
 * @method DateInterval seconds() seconds($seconds = 1) Set the seconds portion of the current interval.
 * @method DateInterval second() second($seconds = 1) Alias for seconds().
 */
class DateInterval extends BaseDateInterval
{
    use DateLocalizationCapabilities;
    /**
     * Interval spec period designators.
     */
    const PERIOD_PREFIX = 'P';
    const PERIOD_YEARS = 'Y';
    const PERIOD_MONTHS = 'M';
    const PERIOD_DAYS = 'D';
    const PERIOD_TIME_PREFIX = 'T';
    const PERIOD_HOURS = 'H';
    const PERIOD_MINUTES = 'M';
    const PERIOD_SECONDS = 'S';

    /**
     * Before PHP 5.4.20/5.5.4 instead of FALSE days will be set to -99999 when the interval instance
     * was created by DateTime:diff().
     */
    const PHP_DAYS_FALSE = -99999;

    /**
     * Determine if the interval was created via DateTime:diff() or not.
     *
     * @param DateInterval $interval
     *
     * @return bool
     */
    private static function wasCreatedFromDiff(DateInterval $interval)
    {
        return $interval->days !== false && $interval->days !== static::PHP_DAYS_FALSE;
    }

    ///////////////////////////////////////////////////////////////////
    //////////////////////////// CONSTRUCTORS /////////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * Create a new DateInterval instance.
     *
     * @param int $years
     * @param int $months
     * @param int $weeks
     * @param int $days
     * @param int $hours
     * @param int $minutes
     * @param int $seconds
     */
    public function __construct(
        $years = 1,
        $months = null,
        $weeks = null,
        $days = null,
        $hours = null,
        $minutes = null,
        $seconds = null
    ) {
        $spec = static::PERIOD_PREFIX;

        $spec .= $years > 0 ? $years.static::PERIOD_YEARS : '';
        $spec .= $months > 0 ? $months.static::PERIOD_MONTHS : '';

        $specDays = 0;
        $specDays += $weeks > 0 ? $weeks * Date::DAYS_PER_WEEK : 0;
        $specDays += $days > 0 ? $days : 0;

        $spec .= $specDays > 0 ? $specDays.static::PERIOD_DAYS : '';

        if ($hours > 0 || $minutes > 0 || $seconds > 0) {
            $spec .= static::PERIOD_TIME_PREFIX;
            $spec .= $hours > 0 ? $hours.static::PERIOD_HOURS : '';
            $spec .= $minutes > 0 ? $minutes.static::PERIOD_MINUTES : '';
            $spec .= $seconds > 0 ? $seconds.static::PERIOD_SECONDS : '';
        }

        if ($spec === static::PERIOD_PREFIX) {
            // Allow the zero interval.
            $spec .= '0'.static::PERIOD_YEARS;
        }

        parent::__construct($spec);
    }

    /**
     * Create a new DateInterval instance from specific values.
     * This is an alias for the constructor that allows better fluent
     * syntax as it allows you to do DateInterval::create(1)->fn() rather than
     * (new DateInterval(1))->fn().
     *
     * @param int $years
     * @param int $months
     * @param int $weeks
     * @param int $days
     * @param int $hours
     * @param int $minutes
     * @param int $seconds
     *
     * @return static
     */
    public static function create(
        $years = 1,
        $months = null,
        $weeks = null,
        $days = null,
        $hours = null,
        $minutes = null,
        $seconds = null
    ) {
        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds);
    }

    /**
     * Provide static helpers to create instances.  Allows DateInterval::years(3).
     *
     * Note: This is done using the magic method to allow static and instance methods to
     *       have the same names.
     *
     * @param string $name
     * @param array  $args
     *
     * @return static
     */
    public static function __callStatic($name, $args)
    {
        $arg = count($args) === 0 ? 1 : $args[0];

        switch ($name) {
            case 'years':
            case 'year':
                return new static($arg);

            case 'months':
            case 'month':
                return new static(null, $arg);

            case 'weeks':
            case 'week':
                return new static(null, null, $arg);

            case 'days':
            case 'dayz':
            case 'day':
                return new static(null, null, null, $arg);

            case 'hours':
            case 'hour':
                return new static(null, null, null, null, $arg);

            case 'minutes':
            case 'minute':
                return new static(null, null, null, null, null, $arg);

            case 'seconds':
            case 'second':
                return new static(null, null, null, null, null, null, $arg);
        }

        throw new InvalidArgumentException();
    }

    /**
     * Create a DateInterval instance from a DateInterval one.  Can not instance
     * DateInterval objects created from DateTime::diff() as you can't externally
     * set the $days field.
     *
     * @param DateInterval $di
     *
     * @throws InvalidArgumentException
     *
     * @return static
     */
    public static function instance(DateInterval $di)
    {
        if (static::wasCreatedFromDiff($di)) {
            throw new InvalidArgumentException('Can not instance a DateInterval object created from DateTime::diff().');
        }

        $instance = new static($di->y, $di->m, 0, $di->d, $di->h, $di->i, $di->s);
        $instance->invert = $di->invert;
        $instance->days = $di->days;

        return $instance;
    }

    ///////////////////////////////////////////////////////////////////
    ///////////////////////// GETTERS AND SETTERS /////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * Get a part of the DateInterval object.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return int
     */
    public function __get($name)
    {
        switch ($name) {
            case 'years':
                return $this->y;

            case 'months':
                return $this->m;

            case 'dayz':
                return $this->d;

            case 'hours':
                return $this->h;

            case 'minutes':
                return $this->i;

            case 'seconds':
                return $this->s;

            case 'weeks':
                return (int) floor($this->d / Date::DAYS_PER_WEEK);

            case 'daysExcludeWeeks':
            case 'dayzExcludeWeeks':
                return $this->d % Date::DAYS_PER_WEEK;

            default:
                throw new InvalidArgumentException(sprintf("Unknown getter '%s'", $name));
        }
    }

    /**
     * Set a part of the DateInterval object.
     *
     * @param string $name
     * @param int    $val
     *
     * @throws InvalidArgumentException
     */
    public function __set($name, $val)
    {
        switch ($name) {
            case 'years':
                $this->y = $val;
                break;

            case 'months':
                $this->m = $val;
                break;

            case 'weeks':
                $this->d = $val * Date::DAYS_PER_WEEK;
                break;

            case 'dayz':
                $this->d = $val;
                break;

            case 'hours':
                $this->h = $val;
                break;

            case 'minutes':
                $this->i = $val;
                break;

            case 'seconds':
                $this->s = $val;
                break;
        }
    }

    /**
     * Allow setting of weeks and days to be cumulative.
     *
     * @param int $weeks Number of weeks to set
     * @param int $days  Number of days to set
     *
     * @return static
     */
    public function weeksAndDays($weeks, $days)
    {
        $this->dayz = ($weeks * Date::DAYS_PER_WEEK) + $days;

        return $this;
    }

    /**
     * Allow fluent calls on the setters... DateInterval::years(3)->months(5)->day().
     *
     * Note: This is done using the magic method to allow static and instance methods to
     *       have the same names.
     *
     * @param string $name
     * @param array  $args
     *
     * @return static
     */
    public function __call($name, $args)
    {
        $arg = count($args) === 0 ? 1 : $args[0];

        switch ($name) {
            case 'years':
            case 'year':
                $this->years = $arg;
                break;

            case 'months':
            case 'month':
                $this->months = $arg;
                break;

            case 'weeks':
            case 'week':
                $this->dayz = $arg * Date::DAYS_PER_WEEK;
                break;

            case 'days':
            case 'dayz':
            case 'day':
                $this->dayz = $arg;
                break;

            case 'hours':
            case 'hour':
                $this->hours = $arg;
                break;

            case 'minutes':
            case 'minute':
                $this->minutes = $arg;
                break;

            case 'seconds':
            case 'second':
                $this->seconds = $arg;
                break;
        }

        return $this;
    }

    /**
     * Get the current interval in a human readable format in the current locale.
     *
     * @return string
     */
    public function forHumans()
    {
        $periods = [
            'year' => $this->years,
            'month' => $this->months,
            'week' => $this->weeks,
            'day' => $this->daysExcludeWeeks,
            'hour' => $this->hours,
            'minute' => $this->minutes,
            'second' => $this->seconds,
        ];

        $parts = [];
        foreach ($periods as $unit => $count) {
            if ($count > 0) {
                array_push($parts, static::translator()->transChoice($unit, $count, [':count' => $count]));
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Format the instance as a string using the forHumans() function.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->forHumans();
    }

    /**
     * Add the passed interval to the current instance.
     *
     * @param DateInterval $interval
     *
     * @return static
     */
    public function add(DateInterval $interval)
    {
        $sign = $interval->invert === 1 ? -1 : 1;

        if (static::wasCreatedFromDiff($interval)) {
            $this->dayz = $this->dayz + $interval->days * $sign;
        } else {
            $this->years = $this->years + $interval->y * $sign;
            $this->months = $this->months + $interval->m * $sign;
            $this->dayz = $this->dayz + $interval->d * $sign;
            $this->hours = $this->hours + $interval->h * $sign;
            $this->minutes = $this->minutes + $interval->i * $sign;
            $this->seconds = $this->seconds + $interval->s * $sign;
        }

        return $this;
    }
}
