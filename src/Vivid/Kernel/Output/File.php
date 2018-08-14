<?php
/**
 * This file contains the File output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\Kernel\Interfaces\OutputInterface;

/**
 * Class File
 *
 * Creating a File output
 *
 * @package Charm\Vivid\Kernel\Output
 */
class File implements OutputInterface
{
    /** @var string wanted filename */
    protected $filename;

    /** @var string full path to file */
    protected $path;

    /** @var string the content type */
    protected $contenttype;

    /** @var string the body content */
    protected $content;

    /**
     * Output factory
     *
     * @param null $val (optional) filename
     *
     * @return self
     */
    public static function make($val = null)
    {
        $x = new self;
        $x->filename = $val;
        return $x;
    }

    /**
     * Build the final output which will be sent to the browser
     *
     * @return string
     */
    public function render()
    {
        // Send content type
        if(empty($this->contenttype)) {
            // Auto set content type based on file extension
            $this->autoContentType();
        }

        header("Content-Type: " . $this->contenttype);

        // Filename
        if(empty($this->filename)) {
            $this->filename = basename($this->path);
        }
        header("Content-Disposition: attachment; filename=" . $this->filename);

        // Return content if set
        if(!empty($this->content)) {
            return $this->content;
        }

        // Return file content
        return file_get_contents($this->path);
    }

    /**
     * Auto set content type
     *
     * @return self
     */
    public function autoContentType() {
        $ct = 'application/octet-stream';

        // Find mime type
        $mime_type = mime_content_type($this->path);
        if($mime_type !== false) {
            $ct = $mime_type;
        }

        $this->contenttype = $ct;
        return $this;
    }

    /**
     * Add the file
     *
     * @param string $val full path to file
     *
     * @return self
     */
    public function withFile($val)
    {
        $this->path = $val;
        return $this;
    }

    /**
     * Add file name
     *
     * @param string $val the file name
     *
     * @return self
     */
    public function withFileName($val)
    {
        $this->filename = $val;
        return $this;
    }

    /**
     * Add response body content
     *
     * @param string $content
     *
     * @return self
     */
    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Add the content type
     *
     * @param string $contenttype the content type
     *
     * @return self
     */
    public function withContentType($contenttype)
    {
        $this->contenttype = $contenttype;
        return $this;
    }

    /**
     * Make this file a download
     *
     * This will prevent the inline display of some files etc.
     *
     * @return self
     */
    public function asDownload()
    {
        $this->contenttype = "application/octet-stream";
        return $this;
    }

}