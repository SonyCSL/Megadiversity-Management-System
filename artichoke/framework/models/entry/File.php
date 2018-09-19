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

class File extends Entry
{
    const BINARY_PREFIX_AUTO = -1;
    const BINARY_PREFIX_NONE = 0;
    const BINARY_PREFIX_KILO = 1;
    const BINARY_PREFIX_MEGA = 2;
    const BINARY_PREFIX_GIGA = 3;

    public function getContentType(): string
    {
        return (string)$this->getDocument('contentType');
    }

    public function getFilename(): string
    {
        return (string)$this->getDocument('filename');
    }

    public function getMetadata(): array
    {
        $m = $this->getDocument('meta');
        if (empty($m)) {
            return [];
        } else {
            // \ArrayObject to array
            return $m->getArrayCopy();
        }
    }

    public function getHash(): string
    {
        return (string)$this->getDocument('md5');
    }

    public function getBytes(int $prefix = self::BINARY_PREFIX_NONE): float
    {
        $bytes = (float)$this->getDocument('length');
        if ($prefix < self::BINARY_PREFIX_NONE || $prefix > self::BINARY_PREFIX_GIGA) {
            // prefix overflow
            return $bytes;
        } else {
            // NONE: 1024^0 = 1
            // KILO: 1024^1
            // MEGA: 1024^2
            // GIGA: 1024^3
            return round($bytes / pow(1024, $prefix), 2);
        }
    }

    public function getBytesString(int $prefix = self::BINARY_PREFIX_AUTO): string
    {
        $bytes = 0.0;
        if ($prefix === self::BINARY_PREFIX_AUTO) {
            // auto detect unit with prefix
            for ($i = self::BINARY_PREFIX_NONE; $i <= self::BINARY_PREFIX_GIGA; $i++) {
                if ($this->getBytes($i) < 1.0) {
                    // prefix: select over 1.0
                    $prefix = $i - 1;
                    break;
                }
            }
        }

        $bytes = $this->getBytes($prefix);

        $res = '';
        switch ($prefix) {
            case self::BINARY_PREFIX_KILO:
                $res = (string)$bytes.' KiB';
                break;
            case self::BINARY_PREFIX_MEGA:
                $res = (string)$bytes.' MiB';
                break;
            case self::BINARY_PREFIX_GIGA:
                $res = (string)$bytes.' GiB';
                break;
            case self::BINARY_PREFIX_NONE:
            default:
                $res = (string)$bytes.' Bytes';
                break;
        }

        return $res;
    }

    public function getThumbnail($base64 = true): string
    {
        $t = $this->getDocument('thumbnailB64');
        if (empty($t)) {
            // thumbnail is not set
            // send alternative image
            return $this->getAlternativeImageBinary($base64);
        } elseif ($base64) {
            // return base64 string
            return $t;
        } else {
            // return binary string
            return base64_decode($t);
        }
    }

    public function getBinary(): string
    {
        return (string)$this->_readFile($this->getId());
    }

    public function getBinaryStream()
    {
        return $this->_readFileStream($this->getId());
    }

    public function getAlternativeImageBinary($base64 = false): string
    {
        // the alternative text is content-type
        $alternative_text = $this->getContentType();

        // default size
        $fontsize = 4;
        $width = 160;
        $height = 120;

        // fontface size
        $fontHeight = imagefontheight($fontsize);
        $fontWidth = imagefontwidth($fontsize) * strlen($alternative_text);

        // create base image
        $NO_IMG = imagecreatetruecolor($width, $height);

        // colors
        $white = imagecolorallocate($NO_IMG, 255, 255, 255);
        $gray = imagecolorallocate($NO_IMG, 150, 150, 150);
        $black = imagecolorallocate($NO_IMG, 0, 0, 0);

        // write
        imagefill($NO_IMG, 0, 0, $gray);
        imageline($NO_IMG, 0, 0, $width, $height, $white);
        imageline($NO_IMG, $width, 0, 0, $height, $white);
        imagestring($NO_IMG, $fontsize, ($width - $fontWidth) / 2, ($height - $fontHeight) / 2, $alternative_text, $black);

        // output
        ob_start();
        imagejpeg($NO_IMG, null, 100);
        $noimg = ob_get_clean();

        if ($base64) {
            return base64_encode($noimg);
        } else {
            return $noimg;
        }
    }
}
