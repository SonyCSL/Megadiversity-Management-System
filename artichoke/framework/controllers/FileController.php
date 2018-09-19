<?php

/**
 *    Copyright 2018 Sony Computer Science Laboratories, Inc.
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace artichoke\framework\controllers;

use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;
use artichoke\framework\models\entry\File;
use artichoke\framework\models\entry\Image;

class FileController extends \artichoke\framework\abstracts\ControllerBase
{
    private $contentType = 'bin';
    private $binary = null;
    private $notFound = false;

    public function __construct()
    {
        // @Override
        // No using page generator
    }

    /**
     * Send file if exist.
     * URL: /file/<File_ID>
     *
     * @param array $args
     */
    public function indexAction(array $args = [])
    {
        if (empty($args[0])) {
            $this->notFound = true;
            return;
        } else {
            $file = new File($args[0]);
        }

        // file not found in database
        if (!$file->exists()) {
            // try to search from temp directory
            $tempf = sys_get_temp_dir().'/'.$args[0];
            if (strpos($args[0], '..') === false && is_readable($tempf)) {
                // file found in temporary
                $this->contentType = mime_content_type($tempf);
                $this->binary = file_get_contents($tempf);
            } else {
                // file not found in both of database and temporary
                $this->notFound = true;
            }
        } else {
            // found, echo binary at destructor
            $this->contentType = $file->getContentType();
            $this->binary = $file->getBinary();
        }
    }

    /**
     * Send image if exist.
     * if the file is not image, send alternative image.
     * URL: /file/image/<File_ID>
     *
     * @param array $args
     */
    public function imageAction(array $args = [])
    {
        if (empty($args[0])) {
            $this->notFound = true;
            return;
        } else {
            $image = new Image($args[0]);
        }

        if (!$image->exists()) {
            // file not found
            $this->notFound = true;
        } else {
            // is it "image" file ?
            if ($image->isImage()) {
                // image file
                $this->setImageParam($image);
            } else {
                // not image, use alternative image binary:
                $this->binary = $image->getAlternativeImageBinary();
                // alternative is jpeg
                $this->contentType = 'jpeg';
            }
        }
    }

    /**
     * Set image parameters.
     */
    private function setImageParam(Image $image)
    {
        // get parameters
        $request = new Requests($_REQUEST);

        $wi = ($request->get('w') === null) ? null : (int)$request->get('w');      // width
        $hi = ($request->get('h') === null) ? null : (int)$request->get('h');      // height
        $bg = ($request->get('bg') === null) ? null : (string)$request->get('bg'); // background colorcode

        if ($wi !== null || $hi !== null) {
            // resized image (with watermark)
            $watermark = (new Server($_SERVER))->rootURL();
            $this->binary = $image->resize($wi, $hi, $bg)->addWatermark($watermark)->getBinary();
        } else {
            // original image
            $this->binary = $image->getBinary();
        }
        $this->contentType = $image->getContentType();
    }

    /**
     * Send thumbnail image if exist.
     * URL: /file/thumbnail/<File_ID>
     *
     * @param array $args
     */
    public function thumbnailAction(array $args = [])
    {
        if (empty($args[0])) {
            $this->notFound = true;
            return;
        } else {
            $file = new File($args[0]);
        }

        if (!$file->exists()) {
            // file not found
            $this->notFound = true;
        } else {
            // output thumbnail
            $mime = $file->getContentType();
            if (strpos($mime, 'image') !== false) {
                // thumbnail content type is same with image
                $this->contentType = $mime;
            } else {
                // default (except image file) is jpeg
                $this->contentType = 'jpeg';
            }
            $this->binary = $file->getThumbnail(false);
        }
    }

    public function __destruct()
    {
        $server = new Server($_SERVER);

        if (!$this->notFound) {
            // Send mime like 'pdf'
            $server->sendMimeType($this->contentType);
            echo $this->binary;
        } else {
            // if not found
            $server->sendHttpStatusCode(404);
        }
    }
}
