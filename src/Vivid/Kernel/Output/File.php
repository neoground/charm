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

    /** @var resource|false file stream */
    protected $stream;

    /** @var int File seek start */
    protected $start;

    /** @var int File seek end */
    protected $end;

    /** @var int|float expires in this amount of seconds */
    protected int|float $expires_in;

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
     * Will read the file in chunks and return the specified parts
     *
     * @see https://stackoverflow.com/a/6914978/6026136
     * @see https://stackoverflow.com/a/39897872/6026136
     *
     * @return string
     */
    public function render()
    {
        // Clean environment
        ob_get_clean();

        // Fire event
        C::Event()->fire('File', 'renderStart');

        // Send content type
        if(empty($this->contenttype)) {
            // Auto set content type based on file extension
            $this->autoContentType();
        }

        // Filename
        if(empty($this->filename)) {
            $this->filename = basename($this->path);
        }

        $this->setGeneralHeaders();

        // Return content if set
        if(!empty($this->content)) {
            header("Content-Length: " . mb_strlen($this->content, '8bit'));
            return $this->content;
        }

        // Read file and return in chunks or specified part
        $this->stream = fopen($this->path, 'rb');
        if ($this->stream === false) {
            return false;
        }
        $this->setHeadersSeekFile();
        $this->streamFile();

        fclose($this->stream);

        // Return empty content after that so the handler continues nicely
        return "";
    }

    /**
     * Stream the file and output it to the browser
     *
     * @return void
     */
    private function streamFile()
    {
        $chunk_size = 1024*1024;
        $i = $this->start;
        while(!feof($this->stream) && $i <= $this->end) {
            $bytesToRead = $chunk_size;
            if(($i+$bytesToRead) > $this->end) {
                $bytesToRead = $this->end - $i + 1;
            }
            $data = fread($this->stream, $bytesToRead);
            echo $data;
            flush();
            $i += $bytesToRead;
        }
    }

    /**
     * Set all basic headers. For seeking additional headers might be added below.
     *
     * @return void
     */
    private function setGeneralHeaders()
    {
        header("Content-Type: " . $this->contenttype);
        $dispo = $this->disposition;
        if(empty($dispo)) {
            $dispo = 'attachment';
        }

        header("Content-Disposition: " . $dispo . "; filename=\"" . $this->filename . "\"");
    }

    /**
     * Set all needed headers and seek file if needed
     *
     * @return void
     */
    private function setHeadersSeekFile()
    {
        $start = 0;
        $size  = filesize($this->path);
        $end   = $size - 1;

        header("Accept-Ranges: 0-".$end);
        header("Last-Modified: ".gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT' );

        if(!empty($this->expires_in)) {
            header("Cache-Control: max-age=" . $this->expires_in . ", public");
            header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $this->expires_in));
        }

        // Seek file if wanted and content present as file
        if (C::Server()->has('HTTP_RANGE')) {
            $c_end = $end;

            [, $range] = explode('=', C::Server()->get('HTTP_RANGE'), 2);
            if (str_contains($range, ',')) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            }else{
                $range = explode('-', $range);
                $c_start = $range[0];

                $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $this->start = $c_start;
            $this->end = $c_end;
            $length = $this->end - $this->start + 1;
            fseek($this->stream, $this->start);
            header('HTTP/1.1 206 Partial Content');
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/".$size);
        } else {
            header("Content-Length: ".$size);
            $this->start = $start;
            $this->end = $end;
        }
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
     * Set expiration of file in this amount of seconds
     *
     * Will set Cache-Control + Expires headers
     *
     * @param int|float $seconds
     *
     * @return $this
     */
    public function expiresIn(int|float $seconds)
    {
        $this->expires_in = $seconds;
        return $this;
    }

}