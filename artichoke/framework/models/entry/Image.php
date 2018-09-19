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

namespace artichoke\framework\models\entry;

class Image extends File
{
    private $is_image_file = false;
    private $extension_type = 'unknown';
    private $edited_image_binary = null;

    public function __construct(string $entry_id)
    {
        parent::__construct($entry_id);

        // validate the file (is it image file?)
        $mime = explode('/', $this->getContentType());
        if ($mime[0] === 'image') {
            // image
            $this->is_image_file = true;
            // extension type (jpg/gif/png/bmp/webp)
            if (!empty($mime[1])) {
                $this->extension_type = $mime[1];
            }
        } else {
            // this is not image, load alternative one
            $this->edited_image_binary = $this->getAlternativeImageBinary();
            $this->extension_type = 'jpeg';
        }
    }

    public function isImage(): bool
    {
        return $this->is_image_file;
    }

    public function getBinary(): string
    {
        // Override
        if ($this->edited_image_binary === null) {
            // original
            return $this->_readFile($this->getId());
        } else {
            // edited
            return $this->edited_image_binary;
        }
    }

    public function getBinaryStream()
    {
        // Override
        if ($this->edited_image_binary === null) {
            // original
            return $this->_readFileStream($this->getId());
        } else {
            // edited
            $new_stream = tmpfile();
            fwrite($new_stream, $this->edited_image_binary);
            return $new_stream;
        }
    }

    public function resize(int $maxWidth = null, int $maxHeight = null, string $bgColorCode = null): self
    {
        // get GD resource
        $GD = $this->_getGD();

        // size default
        if (empty($maxWidth)) {
            $maxWidth = 140;
        }
        if (empty($maxHeight)) {
            $maxHeight = 105;
        }

        if ($GD !== false) {
            // calc new size
            $ratio = (float)imagesy($GD) / imagesx($GD);
            if ($ratio * $maxWidth > $maxHeight) {
                // X-long
                $newHeight = (int)$maxHeight;
                $newWidth = (int)$maxHeight / $ratio;
            } else {
                // Y-long
                $newHeight = (int)$maxWidth * $ratio;
                $newWidth = (int)$maxWidth;
            }

            // create new canvas, paste resized image
            if ($bgColorCode !== null && strlen($bgColorCode) === 6) {
                // with background
                $GD_NEW = imagecreatetruecolor($maxWidth, $maxHeight);

                // color code
                $r = hexdec(substr($bgColorCode, 0, 2));
                $g = hexdec(substr($bgColorCode, 2, 2));
                $b = hexdec(substr($bgColorCode, 4, 2));

                // fill background
                $bgColor = imagecolorallocate($GD_NEW, $r, $g, $b);
                imagefill($GD_NEW, 0, 0, $bgColor);

                // resize
                imagecopyresampled($GD_NEW, $GD, ($maxWidth - $newWidth) / 2, ($maxHeight - $newHeight) / 2, 0, 0, $newWidth, $newHeight, imagesx($GD), imagesy($GD));
            } else {
                // with no background
                $GD_NEW = imagecreatetruecolor($newWidth, $newHeight);

                // resize
                imagecopyresampled($GD_NEW, $GD, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($GD), imagesy($GD));
            }

            // update $edited_image_binary
            $this->_updateEditedBinaryFromGD($GD_NEW);
        }

        return $this;
    }

    public function addWatermark(string $watermark): self
    {
        // get GD resource
        $GD = $this->_getGD();

        if ($GD !== false && !empty($watermark)) {
            // default fontsize
            $fontsize = 5;

            // watermark background height
            $height = imagefontheight($fontsize) + 2;
            // watermark background width
            $width = (imagefontwidth($fontsize) * strlen($watermark)) + 2;

            // create watermark background
            $BG = imagecreatetruecolor($width, $height);

            // write watermark string on background
            $color = imagecolorallocate($GD, 255, 255, 255);
            imagestring($BG, $fontsize, 1, 1, $watermark, $color);

            // paste on original image
            imagecopyresampled($GD, $BG, (imagesx($GD) - imagesx($BG)) / 2, (imagesy($GD) - imagesy($BG)) / 2, 0, 0, $width, $height, imagesx($BG), imagesy($BG));

            // update $edited_image_binary
            $this->_updateEditedBinaryFromGD($GD);
        }

        return $this;
    }

    public function convertTo(string $extension): self
    {
        // get GD resource
        $GD = $this->_getGD();

        if ($GD !== false) {
            // update $edited_image_binary
            $this->_updateEditedBinaryFromGD($GD, $extension);
        }

        return $this;
    }

    private function _getGD()
    {
        return imagecreatefromstring($this->getBinary());
    }

    private function _updateEditedBinaryFromGD($GD, string $extension = null)
    {
        // default extension
        if ($extension === null) {
            $extension = $this->extension_type;
        }

        // open temp memory resource
        $result = false;
        ob_start();
        switch ($extension) {
            case 'gif':
                $result = imagegif($GD, null);
                break;
            case 'png':
                $result = imagepng($GD, null, 6);
                break;
            case 'bmp':
            case 'wbmp':
                $result = imagewbmp($GD, null);
                break;
            case 'jpg':
            case 'jpeg':
                $result = imagejpeg($GD, null, 75);
                break;
            default:
                $result = false;
        }
        $binary = ob_get_clean();

        if ($result) {
            // only in case success, update stream
            $this->edited_image_binary = $binary;
        }
    }
}
