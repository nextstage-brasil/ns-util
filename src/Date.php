<?php

namespace NsUtil;

use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * A class for working with dates and times, with various formatting options.
 */
class Date
{
    /**
     * The DateTime object being managed by this instance.
     *
     * @var DateTime
     */
    private $item;

    /**
     * Create a new Date object for the current date and time.
     *
     * @param string $tz The timezone to use for the new Date object. If null, the default timezone is used.
     */
    public function __construct(string $datetime = 'NOW', ?string $tz = null)
    {
        $date = (new Format($datetime, $tz))->date('arrumar', true);
        $this->item = new DateTime($date);
        if (null !== $tz) {
            $this->item->setTimezone(new DateTimeZone($tz));
        }
        return $this;
    }

    /**
     * Add a duration to the current date and time.
     *
     * @param string $duration A string specifying the duration to add, in the format accepted by the DateInterval constructor.
     * @return self This Date object.
     */
    public function add(string $duration): self
    {

        $this->item->add(DateInterval::createFromDateString($duration));
        return $this;
    }

    /**
     * Subtract a duration from the current date and time.
     *
     * @param string $duration A string specifying the duration to subtract, in the format accepted by the DateInterval constructor.
     * @return self This Date object.
     */
    public function sub(string $duration): self
    {
        $this->item->sub(DateInterval::createFromDateString($duration));
        return $this;
    }

    public function timestamp(): int
    {
        return $this->format('timestamp');
    }

    /**
     * Format the current date and time as a string.
     *
     * @param string $format The format string to use for the output. Defaults to 'Y-m-d'.
     * @param bool $includeTime Whether to include the time component in the output. Defaults to true.
     * @return string The formatted date and time as a string.
     */
    public function format(string $format = 'Y-m-d', bool $includeTime = true): string
    {
        switch ($format) {
            case 'american':
                if ($includeTime) {
                    $out = $this->item->format('Y-m-d H:i:s');
                } else {
                    $out = $this->item->format('Y-m-d');
                }
                break;
            case 'brazil':
                if ($includeTime) {
                    $out = $this->item->format('d/m/Y H:i:s');
                } else {
                    $out = $this->item->format('d/m/Y');
                }
                break;
            case 'iso8601':
            case 'c':
                $out = $this->item->format('c');
                break;
            case 'extenso':
                $out = $this->item->format('d \d\e F \d\e Y');
                // $out = strftime('%d de %B de %Y', $this->item->getTimestamp());
                break;
            case 'timestamp':
                $out = $this->item->getTimestamp();
                break;
            default:
                $out = $this->item->format($format);
                break;
        }
        return $out;
    }
}
