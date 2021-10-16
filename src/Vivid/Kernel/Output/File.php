<?php
/**
 * This file contains the File output class
 */

namespace Charm\Vivid\Kernel\Output;

use Charm\Vivid\C;
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

    /** @var string content disposition */
    protected $disposition;

    /** @var bool output file in chunks? Useful for big files. Default: false */
    protected bool $in_chunks = false;

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
        // Fire event
        C::Event()->fire('File', 'renderStart');

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

        $dispo = $this->disposition;
        if(empty($dispo)) {
            $dispo = 'attachment';
        }

        header("Content-Disposition: " . $dispo . "; filename=\"" . $this->filename . "\"");

        // Return content if set
        if(!empty($this->content)) {
            return $this->content;
        }

        if($this->in_chunks) {
            // Return file in chunks
            $this->readfile_chunked($this->path);

            // Return empty content after that so the handler continues nicely
            return "";
        }

        // Return file content
        return file_get_contents($this->path);
    }

    /**
     * Read a file in chunks
     *
     * @see https://stackoverflow.com/a/6914978/6026136
     *
     * @param string $filename absolute path to file
     * @param bool $retbytes
     *
     * @return bool|int
     */
    public function readfile_chunked($filename, $retbytes = TRUE) {
        // Size of file chunks in bytes
        $chunk_size = 1024*1024;

        $buffer = '';
        $cnt    = 0;
        $handle = fopen($filename, 'rb');

        if ($handle === false) {
            return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, $chunk_size);
            echo $buffer;
            ob_flush();
            flush();

            if ($retbytes) {
                $cnt += strlen($buffer);
            }
        }

        $status = fclose($handle);

        if ($retbytes && $status) {
            return $cnt; // return num. bytes delivered like readfile() does.
        }

        return $status;
    }

    /**
     * Auto set content type
     *
     * @return self
     */
    public function autoContentType() {
        $ct = 'application/octet-stream';

        // Find mime type
        if(!empty($this->path)) {
            $mime_type = mime_content_type($this->path);
            if($mime_type !== false) {
                $ct = $mime_type;
            }
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
        $this->contenttype = 'application/octet-stream';
        $this->disposition = 'attachment';
        return $this;
    }

    /**
     * Show file inline (e.g. in pdf reader in browser)
     *
     * @return $this
     */
    public function inline()
    {
        $this->disposition = 'inline';
        return $this;
    }

    /**
     * File is attachment = file download
     *
     * @return $this
     */
    public function asAttachment()
    {
        $this->disposition = 'attachment';
        return $this;
    }

    /**
     * Output file in chunks. This is useful for big files.
     *
     * @return $this
     */
    public function inChunks()
    {
        $this->in_chunks = true;
        return $this;
    }

}