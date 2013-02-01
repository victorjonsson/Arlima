<?php

/**
 * Class that creates image versions (of any size) on the fly.
 *
 * @package Arlima
 * @since 2.5.7
 * @requires WP 3.5
 */
class Arlima_ImageVersionManager
{

    const META_KEY = 'arlima-images';
    const VERSION_PREFIX = 'arlima_mw';
    
    /**
     * @var int
     */
    private $attach_id;

    /**
     * @var array
     */
    private static $upload_dir_data;

    /**
     * @var Arlima_Plugin|null
     */
    private $plugin;

    /**
     * @param int $id
     * @param Arlima_Plugin $plugin
     */
    public function __construct($id, $plugin=null)
    {
        $this->attach_id = $id;
        $this->plugin = $plugin;
    }

    /**
     * @param string $key
     * @return array
     */
    private static function uploadDirData($key = null)
    {
        if( self::$upload_dir_data === null) {
            self::$upload_dir_data = wp_upload_dir();
            if( self::$upload_dir_data === false || false !== self::$upload_dir_data['error']) {
                self::$upload_dir_data = array(
                    'basedir' => WP_CONTENT_DIR .'/uploads',
                    'baseurl' => home_url() .'/wp-content/uploads'
                );
            }
        }
        return $key === null ? self::$upload_dir_data : self::$upload_dir_data[$key];
    }

    /**
     * Removes all generated versions when attachments gets deleted
     */
    public static function registerFilters()
    {
        add_action('delete_attachment', 'Arlima_ImageVersionManager::removeVersions');
    }

    /**
     * Removes all image versions created for given attachment
     * @param int $attach_id
     */
    public static function removeVersions($attach_id = null)
    {
        $meta = wp_get_attachment_metadata($attach_id);
        if( !empty($meta) && !empty($meta[self::META_KEY])) {
            $manager = new self($attach_id);
            foreach($manager->getVersions($meta) as $path) {
                if( file_exists($path) )
                    @unlink($path);
            }
            unset($meta[self::META_KEY]);
            wp_update_attachment_metadata($attach_id, $meta);
        }
    }

    /**
     * Generates a new version
     * @param int $max_width
     * @return string
     */
    function getVersionURL($max_width)
    {
        $file = get_post_meta( $this->attach_id, '_wp_attached_file', true );
        $version_url = false;
        if( $file ) {

            $meta = wp_get_attachment_metadata($this->attach_id);

            // Version already generated
            if( !empty($meta[self::META_KEY]) && isset($meta[self::META_KEY][$max_width]) ) {
                $version_url = $this->generateFileURL($meta[self::META_KEY][$max_width]);
            }
            else {

                // Try to create new version

                $version_file = $this->generateVersionName($file, $max_width);
                $file_full_path = self::uploadDirData('basedir').'/'.$file;
                $editor = wp_get_image_editor($file_full_path);

                if( is_wp_error($editor) ) {
                    trigger_error('Failed loading WP image editor with message: '.$editor->get_error_message(), E_USER_ERROR);
                    $version_url = $this->generateFileURL($file);
                }
                elseif( $this->canGenerateVersion($file_full_path, $max_width) ) {
                    $editor->set_quality( apply_filters('arlima_image_quality', $this->plugin->getSetting('image_quality', 100)) );
                    if( $editor->resize($max_width, false) ) {

                        if( ($error = $editor->save(self::uploadDirData('basedir').'/'.$version_file)) instanceof WP_Error ) {
                            trigger_error('Failed saving resized image with message: '.$error->get_error_message(), E_USER_ERROR);
                            $version_url = $this->generateFileURL($file);
                        } else {
                            $this->saveGeneratedVersion($meta, $version_file, $max_width);
                            $version_url = $this->generateFileURL($version_file);
                        }

                    } else {
                        trigger_error($editor->get_error_message(), E_USER_ERROR);
                        $version_url = $this->generateFileURL($file);
                    }
                }
                else {
                    // We can not generate a version out of this file, use original source
                    $this->saveGeneratedVersion($meta, $file, $max_width);
                    $version_url = $this->generateFileURL($file);
                }
            }
        }
        return $version_url;
    }

    /**
     * @param $meta
     * @param $version_file
     * @param $max_width
     * @return array
     */
    private function saveGeneratedVersion($meta, $version_file, $max_width)
    {
        if( empty($meta[self::META_KEY]) )
            $meta[self::META_KEY] = array();

        $meta[self::META_KEY][$max_width] = $version_file;
        wp_update_attachment_metadata($this->attach_id, $meta);
        return $meta;
    }

    /**
     * Do not upsize to small png images
     * @param $file
     * @param $max_width
     * @return bool
     */
    private function canGenerateVersion($file, $max_width)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION)) != 'png' ||
                current(getimagesize($file)) > $max_width;
    }

    /**
     * @param $file
     * @return bool|mixed|string
     */
    private function generateFileURL($file)
    {
        $uploads = self::uploadDirData();

        if ( 0 === strpos($file, $uploads['basedir']) ) //Check that the upload base exists in the file location
            return str_replace($uploads['basedir'], $uploads['baseurl'], $file); //replace file location with url location
        elseif ( false !== strpos($file, 'wp-content/uploads') )
            return $uploads['baseurl'] . substr( $file, strpos($file, 'wp-content/uploads') + 18 );
        else
            return $uploads['baseurl'] . "/$file"; //Its a newly uploaded file, therefor $file is relative to the basedir.
    }

    /**
     * @param string $file
     * @param int $max_width
     * @return string
     */
    private function generateVersionName($file, $max_width)
    {
        $info = pathinfo($file);
        return $info['dirname'] .'/'. $info['filename'] .'-' .self::VERSION_PREFIX. $max_width .'.'. $info['extension'];
    }

    /**
     * Get paths to all generated arlima version
     * @param array|null $meta
     * @param bool $as_url
     * @return array
     */
    function getVersions($meta = null, $as_url = false)
    {
        if( $meta === null )
            $meta = wp_get_attachment_metadata($this->attach_id);

        $paths = array();
        $dir = self::uploadDirData('basedir').'/';
        $file_name_regex = '/'.self::VERSION_PREFIX.'([0-9]+)\.([a-zA-Z]+)/';
        if( $meta && !empty($meta[self::META_KEY]) ) {
            foreach( $meta[self::META_KEY] as $version ) {
                if( preg_match_all($file_name_regex, $version, $m) ) {
                    $paths[] = $dir . $version;
                    // some paths may be the same as the original source since we
                    // do not scale up images
                }
            }
        }

        if( $as_url ) {
            foreach($paths as $i => $file)
                $paths[$i] = $this->generateFileURL( $file );
        }

        return $paths;
    }
}