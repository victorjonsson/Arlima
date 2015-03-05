<?php

/**
 * Object representing an article list.
 *
 * @package Arlima
 * @since 1.0
 */
class Arlima_List
{
    const ERROR_PREVIEW_VERSION_NOT_FOUND = 99200;

    const STATUS_PREVIEW = 2;
    const STATUS_PUBLISHED = 1;
    const STATUS_SCHEDULED = 3;
    const STATUS_EMPTY = -1; // the status it will have when its created and no version yet exists
    const QUERY_ARG_PREVIEW = 'arlima-preview';

    /**
     * @var bool|array
     */
    private $post_ids = false;

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var int
     */
    private $created = 0;

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $slug = '';

    /**
     * @var int
     */
    private $status = self::STATUS_EMPTY;

    /**
     * @var array
     */
    private $version = array('id' => 0, 'created'=>0, 'user_id'=>0);

    /**
     * @var int
     */
    private $maxlength = 50;

    /**
     * @var array
     */
    private $version_history = array();

    /**
     * @var array
     */
    private $scheduled_versions = array();

    /**
     * Tells us whether or not this is a list imported from a remote server
     * @var bool
     */
    private $is_imported = false;

    /**
     * @var array
     * @see Arlima_List::getDefaultOptions
     */
    private $options = array();

    /**
     * @var Arlima_Article[]
     */
    private $articles = array();

    /**
     * @var bool
     */
    private $exists = false;

    /**
     * @param bool $exists
     * @param int $id
     * @param bool $is_imported
     */
    function __construct($exists = false, $id = 0, $is_imported = false)
    {
        $this->options = self::getDefaultListOptions();
        $this->exists = $exists;
        $this->is_imported = $is_imported;
        $this->id = $id;
    }

    /**
     * @return array
     */
    static function getDefaultListOptions()
    {
        return array(
            'template' => 'article',
            'available_templates' => false, // not set meaning all will be available
            'pages_to_purge' => '',
            'supports_sections' => "0", // translates to bool false
            'allows_template_switching' => "1",
            'before_title' => '<h2>',
            'after_title' => '</h2>'
        );
    }

    /**
     * Tells whether or not this arlima list exists in the database
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * Tells whether or not this arlima list is loaded from
     * a remote host
     * @return bool
     */
    public function isImported()
    {
        return $this->is_imported;
    }

    /**
     * Tells whether or not the list contains a preview version
     * @return bool
     */
    public function isPreview()
    {
        return $this->getStatus() == self::STATUS_PREVIEW;
    }

    /**
     * Tells whether or not the list contains a scheduled version
     * @return bool
     */
    public function isScheduled()
    {
        return $this->getStatus() == self::STATUS_SCHEDULED;
    }

    /**
     * @return bool
     */
    public function isPublished()
    {
        return $this->getStatus() == self::STATUS_PUBLISHED;
    }

    /**
     * Tells whether or not this list allows use of given template
     * @param string $template
     * @return bool
     */
    public function isAvailable($template)
    {
        if( $this->hasOption('available_templates') ) {
            return in_array(basename($template), $this->getOption('available_templates'));
        }
        return true;
    }

    /**
     * Whether or not admins can create "sections" in the list
     * @return bool
     */
    public function isSupportingSections()
    {
        return !empty($this->options['supports_sections']);
    }

    /**
     * Whether or not editors is allowed to switch template
     * on specific articles in the list
     * @return bool
     */
    public function isSupportingEditorTemplateSwitch()
    {
        return $this->getOption('allows_template_switching') === null || $this->getOption('allows_template_switching');
    }

    /**
     * Returns information about this version of the list
     * @see Arlima_List::getVersionAttribute()
     * @return array
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $name Either 'user_id', 'created' or 'id'
     * @return string
     */
    public function getVersionAttribute($name)
    {
        return isset($this->version[$name]) ? $this->version[$name] : null;
    }

    /**
     * A list with the latest created versions of this list
     * @deprecated
     * @return array
     */
    public function getVersions()
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_List::getPublishedVersions');
        return $this->getPublishedVersions();
    }

    /**
     * @deprecated
     * @param array $versions
     */
    public function setVersions($versions)
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_List::setPublishedVersions');
        $this->setPublishedVersions($versions);
    }

    /**
     * @param array $versions
     */
    public function setPublishedVersions($versions)
    {
        $this->version_history = $versions;
    }

    /**
     * @return array
     */
    public function getPublishedVersions()
    {
        return $this->version_history;
    }

    /**
     * @param array $scheduled_versions
     */
    public function setScheduledVersions($scheduled_versions) {
        $this->scheduled_versions = $scheduled_versions;
    }

    /**
     * @return array
     */
    public function getScheduledVersions() {
        return $this->scheduled_versions;
    }

    /**
     * @param array $version_data
     */
    public function setVersion($version_data)
    {
        $this->version = $version_data;
        $this->status = $version_data['status'];
    }

    /**
     * Get a list option (also has an aliased function named opt())
     * @param string $name
     * @param mixed $default
     * @return string|null
     */
    public function getOption($name, $default=null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Alias for getOption($name, $default=null)
     * @param $name
     * @param $default
     * @return string|null
     */
    public function opt($name, $default=null)
    {
        return $this->getOption($name, $default);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption($name)
    {
        return !empty($this->options[$name]);
    }

    /**
     * @param string $name
     * @param string $val
     */
    public function setOption($name, $val)
    {
        $this->options[$name] = $val;
    }

    /**
     * @deprecated
     * @see Arlima_List::getId()
     * @return int
     */
    public function id()
    {
        Arlima_Utils::warnAboutDeprecation(__METHOD__, 'Arlima_List::getId');
        return $this->id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns a list with id numbers of the posts that has a connection
     * to one or more articles in this list
     * @return array
     */
    public function getContainingPosts()
    {
        if( $this->post_ids === false ) {
            $posts = array();
            foreach($this->getArticles() as $article) {
                if( $post_id = $article->getPostId() ) {
                    $posts[$post_id] = 1;
                }
                foreach ($article->getChildArticles() as $child) {
                    if ( $post_id = $child->getPostId() ) {
                        $posts[$post_id] = 1;
                    }
                }
            }
            $this->post_ids = array_keys($posts);
        }
        return $this->post_ids;
    }

    /**
     * Tells whether or not this list contains one or more articles connected
     * to the post with given id
     * @param int $post_id
     * @return bool
     */
    public function containsPost($post_id)
    {
        return in_array($post_id, $this->getContainingPosts());
    }

    /**
     * @param array $articles
     */
    public function setArticles($articles)
    {
        $this->articles = $articles;
        $this->post_ids = false;
    }

    /**
     * @return Arlima_Article[]
     */
    public function getArticles()
    {
        return $this->articles;
    }

    /**
     * @param int $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param int $maxlength
     */
    public function setMaxlength($maxlength)
    {
        $this->maxlength = $maxlength;
    }

    /**
     * @return int
     */
    public function getMaxlength()
    {
        return $this->maxlength;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Merge in new options
     * @param array $new_options
     * @return array
     */
    public function addOptions($new_options)
    {
        $this->options = array_merge($this->options, $new_options);
        return $this->options;
    }

    /**
     * Set options for the list
     * @see Arlima_List::addOptions()
     * @param array $options
     */
    public function setOptions($options)
    {
        foreach($options as $name => $val) {
            if( is_numeric($val) )
                $options[$name] = (int)$val;
        }
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $this->options['hidden_templates'] = Arlima_CMSFacade::load()->applyFilters('arlima_hidden_templates', array(), $this);
        return $this->options;
    }

    /**
     * Will return the HTMl element used as header for articles in this list.
     * If something other then a valid header element is used this function
     * will return an empty string
     * @return string
     */
    function getTitleElement()
    {
        $title_parts = explode('>', $this->options['before_title']); // todo: use regexp instead
        $element = trim(str_replace('<', '', $title_parts[0]));
        $space_pos = strpos($element, ' ');
        if ( $space_pos !== false ) {
            $element = substr($element, 0, $space_pos);
        }

        if ( in_array($element, array('h1', 'h2', 'h3', 'h4', 'h5', 'p', 'div', 'header')) ) {
            return $element;
        } else {
            return '';
        }
    }

    /**
     * @return int
     */
    function numArticles()
    {
        return count($this->articles);
    }

    /**
     * Get the modification date (timestamp) when this version of the list
     * was created
     * @return int
     */
    function lastModified()
    {
        return !empty($this->version['created']) ? $this->version['created'] : $this->created;
    }

    /**
     * @return bool
     */
    function isLatestPublishedVersion()
    {
        return !$this->isPreview() &&
            isset($this->version['id']) &&
            (empty($this->version_history) || $this->version_history[0]['id'] == $this->version['id']);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $arr = array();
        foreach($this as $key => $val) {
            if( $key !== 'preview' && $key != 'post_ids' && $key != 'articles') {
                $arr[$key] = $val;
            }
        }

        // Convert articles to arrays
        $arr['articles'] = array();
        foreach($this->getArticles() as $i => $art) {
            $arr['articles'][$i] = $art->toArray();
        }

        // Backwards compat
        $arr['versions'] = array();
        foreach( $this->version_history as $ver ) {
            $arr['versions'][] = $ver['id'];
        }

        return $arr;
    }

    /**
     * @return Arlima_ListBuilder
     */
    public static function builder()
    {
        return new Arlima_ListBuilder();
    }

}
