<?php


/**
 * Object representing an (read-only) Arlima article. This class implements ArrayAccess and
 * Countable which makes it possible to treat the object as an ordinary array
 *
 * @package Arlima
 * @since 3.1
 */
class Arlima_Article implements ArrayAccess, Countable {

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var bool
     */
    private $has_img = false;

    /**
     * @var null|string
     */
    private $url = null;

    /**
     * @param array $data
     */
    public function __construct($data=array())
    {
        $defaults = array(
            'content' => '',
            'title' => 'Unknown',
            'created' => 0,
            'published' => 0,
            'parent' => -1,
            'children' => array(),
            'options' => array(),
            'post' => 0,
            'id' => 0,
            'size' => 18
        );
        
        $this->data = array_merge($defaults, $data);
        $this->has_img = !empty($this->data['image']) && ( !empty($this->data['image']['attachment']) || !empty($this->data['image']['url']));
    }

    /**
     * Get an article option, will return the value of $default if the option does'nt exist
     * @param string $opt
     * @param mixed $default=false
     * @return string
     */
    function opt($opt, $default=false)
    {
        return isset($this->data['options'][$opt]) ? $this->data['options'][$opt] : $default;
    }

    /**
     * Get the URL of the article which is either the URL of the post connected to the article
     * or the value of the option "overridingURL". This function will return an empty string if
     * the article does'nt have a URL
     *
     * @return string
     */
    public function getURL()
    {
        if( $this->url === null ) {
            $this->url = '';
            if( $overriding = $this->opt('overridingURL') ) {
                $this->url =  $overriding;
            } elseif( $this->hasPost() ) {
                $this->url = Arlima_CMSFacade::load()->getPostURL($this->data['post']);
            }
        }
        return $this->url;
    }

    /**
     * Whether or not this article has a streamer (streamers is made out of the options
     * streamerType, streamerContent and streamerColor)
     * @return bool
     */
    function hasStreamer()
    {
        return (bool)($this->opt('streamerType') && $this->opt('streamerContent'));
    }

    /**
     * Whether or not this Arlima article is connected to a post
     * @return bool
     */
    function hasPost()
    {
        return !empty($this->data['post']);
    }

    /**
     * Whether or not this article is published. If this function returns false it means that the article
     * has aa publish date set to a future date.
     * @return bool
     */
    function isPublished()
    {
        return empty($this->data['published']) || $this->data['published'] <= Arlima_Utils::timeStamp();
    }

    /**
     * The article is considered "empty" if it's missing title, content, image and child articles
     * @return bool
     */
    function isEmpty()
    {
        return empty($this->data['title']) &&
                empty($this->data['content']) &&
                !$this->hasImage() &&
                !$this->hasChildren();
    }

    /**
     * Whether or not this article is scheduled to only be displayed at certain hours of the day
     * and certain days of the week. (Notice! This has nothing to do with whether or not this article
     * belongs to a scheduled list version)
     * @return bool
     */
    function isScheduled()
    {
        return $this->opt('scheduled') ? true:false;
    }

    /**
     * Whether or not this article is a child of another article
     * @return bool
     */
    public function isChild()
    {
        return $this->getParentIndex() > -1;
    }

    /**
     * Tells if this article has an image
     * @return bool
     */
    function hasImage()
    {
        return $this->has_img;
    }

    /**
     * Get the URL of article image. Returns empty string if not image
     * is connected to the article
     * @return string
     */
    function getImageURL()
    {
        if( $this->has_img ) {
            if( !empty($this->data['image']['attachment']) ) {
                return Arlima_CMSFacade::load()->getImageURL($this->data['image']['attachment']);
            }
            return $this->data['image']['url'];
        }
        return '';
    }

    /**
     * Get size name of possibly connected image
     * @return string
     */
    function getImageSize()
    {
        return $this->getImageData('size');
    }

    /**
     * Get aligment of possibly connected image
     * @return string
     */
    function getImageAlignment()
    {
        return $this->getImageData('alignment');
    }

    /**
     * Get id of possibly connected image. Will return empty string if no images is attached to the article
     * @return int|string
     */
    function getImageId()
    {
        return $this->getImageData('attachment'); // @todo rename 'attachment' to 'imageId'
    }

    /**
     * @param string $type
     * @param string $default
     * @return string
     */
    private function getImageData($type, $default='')
    {
        return $this->has_img && !empty($this->data['image'][$type]) ? $this->data['image'][$type] : $default;
    }

    /**
     * Get "size" of the article. This is normally used as font-size of the article
     * title when the article gets rendered
     * @return int
     */
    function getSize()
    {
        return $this->data['size'];
    }

    /**
     * Whether or not the article should be rendered. It should not be rendered in case the article
     * is considered to be empty (see ->isEmpty()) or the article is scheduled to be displayed at another time
     * or if it's not yet published
     *
     * @return bool
     */
    function canBeRendered()
    {
        return $this->isPublished() &&
                !$this->isEmpty() &&
                (!$this->isScheduled() || $this->isInScheduledInterval($this->opt('scheduledInterval')));
    }

    /**
     * Will try to parse a schedule-interval-formatted string and determine
     * if we're currently in the time interval
     *
     * @example
     *  isInScheduledInterval('*:*'); // All days of the week and all hours of the day
     *  isInScheduledInterval('Mon,Tue,Fri:*'); // All hours of the day on monday, tuesday and friday
     *  isInScheduledInterval('*:10-12'); // The hours 10, 11 and 12 all days of the week
     *  isInScheduledInterval('Thu:12,15,18'); // Only on thursday and at the hours 12, 15 and 18
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

    /**
     * Get the body content of the article
     * @return string
     */
    function getContent()
    {
        return $this->data['content'];
    }

    /**
     * Get title of the article. Set parameter $linebreak_replace to a br-tag if you want to convert a double-underscore to a linebreak
     * @param string $linebreak_replace
     * @param bool $entity_encode
     * @param bool $link_wrap
     * @return string
     */
    function getTitle($linebreak_replace=' ', $entity_encode=false, $link_wrap=false)
    {
        $title = $entity_encode ? htmlentities($this->data['title'], ENT_NOQUOTES, 'utf-8') : $this->data['title'];
        $title = str_replace('__', $linebreak_replace, $title);
        if( $link_wrap )
            $title = Arlima_Utils::linkWrap($this, $title);
        return $title;
    }

    /**
     * Get id of possibly connected post
     * @return int
     */
    function getPostId()
    {
        return $this->data['post'];
    }

    /**
     * Will return -1 if the article does'nt have a parent article
     * @return int
     */
    function getParentIndex()
    {
        return (int)$this->data['parent'];
    }

    /**
     * @return int
     */
    function getId()
    {
        return $this->data['id'];
    }

    /**
     * Get an unix timestamp (in seconds) of when the article was created
     * @return int
     */
    function getCreationTime()
    {
        return $this->data['created'];
    }

    /**
     * Get an unix timestamp (in seconds) of when the article is considered to be published
     * @return int
     */
    function getPublishTime()
    {
        return $this->data['published'];
    }

    /**
     * @return Arlima_Article[]
     */
    function getChildArticles()
    {
        $children = array();
        foreach($this->data['children'] as $child_data) {
            $children[] = new Arlima_Article($child_data);
        }
        return $children;
    }

    /**
     * @param array $article_data
     */
    function addChild($article_data) {
        $this->data['children'][] = $article_data instanceof Arlima_Article ? $article_data->toArray() : $article_data;
    }

    /**
     * Tells whether or not the article is a parent of other articles (see getChildArticles())
     * @return bool
     */
    function hasChildren()
    {
        return !empty($this->data['children']);
    }

    /**
     * Tells whether or not this articles only purpose is to point out where
     * one section ends and another section begins
     * @return bool
     */
    function isSectionDivider()
    {
        return (bool)$this->opt('sectionDivider');
    }

    /**
     * Tells whether or not the purpose of this article is to execute (include) a php-file
     * on the local file system
     * @return bool
     */
    function isFileInclude()
    {
        return (bool)$this->opt('fileInclude');
    }

    /**
     * Get all data representing the article as an array
     * @return array
     */
    function toArray()
    {
        // Copy
        $data = $this->data;
        $data['url'] = $this->getURL();
        return $data;
    }


    /* * * * * ArrayAccess / Countable Impl * * * * */


    /**
     * @ignore
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
        // todo: decide whether or not this should be allowed
       // throw new Exception('Modifying an Arlima_Article object is not allowed');
       // Arlima_Utils::warnAboutDeprecation(__METHOD__, 'no real alternative, recreate article object using Arlima_ListVersionRepository::createArticle');
    }

    /**
     * @ignore
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->deprecatedDataAccess();
        return isset($this->data[$offset]);
    }

    /**
     * @ignore
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->deprecatedDataAccess();
        unset($this->data[$offset]);
    }

    /**
     * @ignore
     * @param mixed $offset
     * @return mixed|null|string
     */
    public function offsetGet($offset)
    {
        $this->deprecatedDataAccess();
        if( $offset == 'url' ) {
            return $this->getURL();
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @ignore
     */
    private function deprecatedDataAccess()
    {
        // @todo: decide if this should be allowed or not...
        //if ( WP_DEBUG ) {
        //    trigger_error('Using article object as an array which is deprecated as of version 3.1 of Arlima. See wiki about more info...', E_USER_WARNING);
       // }
    }

    /**
     * @ignore
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /* * * * Setter/getters * * * */

    /**
     * @ignore
     * @param $key
     * @return mixed|null|string
     */
    public function __get($key) {
        return $this->offsetGet($key);
    }

    /**
     * @ignore
     * @param $key
     * @param $val
     * @return mixed|null|string
     */
    public function __set($key, $val) {
        return $this->offsetGet($key, $val);
    }

}