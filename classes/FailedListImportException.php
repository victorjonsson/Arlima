<?php


/**
 * Exception thrown when loading of an external list fails
 *
 * @package Arlima
 * @since 3.1
 */
class Arlima_FailedListImportException extends Exception {

    /**
     * @var string
     */
    private $url = 'Unknown';

    /**
     * @param $url
     */
    public function setURL($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->url;
    }

}