<?php


/**
 * Object representing an (read-only) Arlima article
 *
 * @NOTICE This class is not yet in use!
 */
class Arlima_Article implements ArrayAccess {

    /**
     * @var array
     */
    private $data = array();

    /**
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $opt
     * @return string
     */
    function opt($opt)
    {
        return isset($this->data['options'][$opt]) ? $this->data['options'][$opt]:null;
    }

    /**
     * @return bool|string
     */
    public function url()
    {
        if( $overriding = $this->opt('overridingURL') ) {
            return $overriding;
        } elseif( $this->hasPost() ) {
            return get_permalink($this->data['post']);
        }
        return '';
    }

    /**
     * @return bool
     */
    function hasPost()
    {
        return !empty($this->data['post']);
    }

    /**
     * @return bool
     */
    function isPublished()
    {
        return $this->data['published'] >= Arlima_Utils::timeStamp();
    }

    /**
     * @return bool
     */
    function isScheduled()
    {
        return $this->opt('scheduled') ? true:false;
    }

    /**
     * Whether or not this article should be rendered.
     * It should not be rendered in case...
     *  ... the article is missing both title and content
     *  ... the article is scheduled to be displayed at another time
     *  ... it's not published
     *
     * @return bool
     */
    function canBeRendered()
    {
        return $this->isPublished() &&
                (!empty($this->data['title']) || !empty($this->data['content'])) &&
                (!$this->isScheduled() || $this->isInScheduledInterval($this->opt('scheduledInterval')));
    }

    /**
     * Will try to parse a schedule-interval-formatted string and determine
     * if we're currently in this time interval
     * @example
     *  isInScheduledInterval('*:*');
     *  isInScheduledInterval('Mon,Tue,Fri:*');
     *  isInScheduledInterval('*:10-12');
     *  isInScheduledInterval('Thu:12,15,18');
     *
     * @param string $schedule_interval
     * @return bool
     */
    protected function isInScheduledInterval($schedule_interval)
    {
        $interval_part = explode(':', $schedule_interval);
        if ( count($interval_part) == 2 ) {

            // Check day
            if ( trim($interval_part[0]) != '*' ) {

                $current_day = strtolower(date('D', Arlima_Utils::timeStamp()));
                $days = array();
                foreach (explode(',', $interval_part[0]) as $day) {
                    $days[] = strtolower(substr(trim($day), 0, 3));
                }

                if ( !in_array($current_day, $days) ) {
                    return false; // don't show article today
                }

            }

            // Check hour
            if ( trim($interval_part[1]) != '*' ) {

                $current_hour = (int)date('H', Arlima_Utils::timeStamp());
                $from_to = explode('-', $interval_part[1]);
                if ( count($from_to) == 2 ) {
                    $from = (int)trim($from_to[0]);
                    $to = (int)trim($from_to[1]);
                    if ( $current_hour < $from || $current_hour > $to ) {
                        return false; // don't show article this hour
                    }
                } else {
                    $hours = array();
                    foreach (explode(',', $interval_part[1]) as $hour) {
                        $hours[] = (int)trim($hour);
                    }

                    if ( !in_array($current_hour, $hours) ) {
                        return false; // don't show article this hour
                    }
                }
            }
        }

        return true;
    }


    /* * * * * ArrayAccess Impl * * * * */



    public function offsetSet($offset, $value)
    {
        throw new Exception('Modifying an Arlima_Article object is not allowed');
    }

    public function offsetExists($offset)
    {
        $this->deprecatedDataAccess();
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        $this->deprecatedDataAccess();
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        $this->deprecatedDataAccess();
        if( $offset == 'url' ) {
            return $this->url();
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    private function deprecatedDataAccess()
    {
        if ( WP_DEBUG ) {
            trigger_error('Using article object as an array which is deprecated as of version 3.1 of Arlima. See wiki about more info...', E_USER_WARNING);
        }
    }


    /* * * * Setter/getters * * * */


    public function __get($key) {
        return $this->offsetGet($key);
    }

    public function __set($key, $val) {
        return $this->offsetGet($key, $val);
    }

}