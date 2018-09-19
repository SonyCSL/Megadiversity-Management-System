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

class Metadata
{
    private $filepath;
    private $mime;
    private $is_image_file = false;
    private $is_temp_file = false;
    private $data = [];
    private $embedded_thumbnail;

    public function __construct($file)
    {
        // $val type is [string filepath] or [resource]
        $type = gettype($file);
        if ($type === 'string' && is_readable($file)) {
            // filepath (standard)
            $this->filepath = $file;
        } elseif ($type === 'resource') {
            // convert to temporary file
            $this->filepath = tempnam(sys_get_temp_dir(), 'ARTICHOKE');
            file_put_contents($this->filepath, $file);
            $this->is_temp_file = true;
        } else {
            // invalid argument
            return;
        }

        $this->mime = mime_content_type($this->filepath);
        $this->data = $this->_readExif();

        // is the target file image ? (jpg,png,bmp,tiff,..)
        if (strpos($this->mime, 'image/') !== false) {
            $this->is_image_file = true;
        }
    }

    public function getDatetime()
    {
        $res = null;
        if (isset($this->data['IFD0']['DateTime'])) {
            $res = new \DateTime((string)$this->data['IFD0']['DateTime']);
        } elseif (isset($this->data['EXIF']['DateTimeOriginal'])) {
            $res = new \DateTime((string)$this->data['EXIF']['DateTimeOriginal']);
        } elseif (isset($this->data['EXIF']['DateTimeDigitized'])) {
            $res = new \DateTime((string)$this->data['EXIF']['DateTimeDigitized']);
        } else {
            return;
        }

        return $res;
    }

    public function getThumbnail(bool $base64 = true): string
    {
        // target file is not image (jpg,png,bmp,tiff,..)
        if (!$this->is_image_file) {
            return '';
        }

        if (empty($this->embedded_thumbnail)) {
            // thumbnail not exists in exif
            $thumbnail = self::imageResize($this->filepath, $this->mime, 160, 120);
        } else {
            $thumbnail = $this->embedded_thumbnail;
        }

        $res = '';
        // convert output
        if (empty($thumbnail)) {
            // any error occured
            $res = '';
        } elseif ($base64) {
            // base64 string
            $res = base64_encode($thumbnail);
        } else {
            // binary string
            $res = $thumbnail;
        }

        return $res;
    }

    /**
     * Get keywords array as tags.
     * iptc metadata is embedded at 'APP13' marker as binary.
     * The function iptcparse() returns tags array.
     * The KEY is formatted like '2#025', this means "<Record Number> # <Tag ID>".
     * '2#025' point to "2:IPTC ApplicationRecord Tags -> 25:Keywords". In detail, see list link below.
     *
     * @see https://sno.phy.queensu.ca/~phil/exiftool/TagNames/IPTC.html (IPTC tags list with code)
     * @see https://iptc.org/standards/photo-metadata/iptc-standard/ (Photo metadata standard)
     *
     * @return array
     */
    public function getTags(): array
    {
        // target file is not image (jpg,png,bmp,tiff,..)
        if (!$this->is_image_file) {
            return [];
        }

        getimagesize($this->filepath, $info);
        if (isset($info['APP13'])) {
            $iptc = iptcparse($info['APP13']);
        } else {
            $iptc = false;
        }

        if ($iptc !== false && isset($iptc['2#025'])) {
            // "keywords" tag
            return (array)$iptc['2#025'];
        } else {
            return [];
        }
    }

    public function getDescription(): string
    {
        // reading IFD0->ImageDescription
        if (isset($this->data['IFD0']['ImageDescription'])) {
            return (string)$this->data['IFD0']['ImageDescription'];
        } else {
            return '';
        }
    }

    /**
     * Get GeoJSON strings from exif-gps section.
     * The function exif_read_data() returns ['GPS'] section (sub-section of IFD0) if exist.
     * This method converts <EXIF GPS Array> to <GeoJSON Point Object Array>
     *
     * @see https://tools.ietf.org/html/rfc7946 (RFC 7946: The GeoJSON Format)
     *
     * @return array
     */
    public function getGeoJsonArray(): array
    {
        if (isset($this->data['GPS']['GPSLatitude']) && isset($this->data['GPS']['GPSLongitude'])) {
            // degrees, minutes, seconds
            for ($i = 0; $i < 3; $i++) {
                $gpslats[$i] = explode('/', $this->data['GPS']['GPSLatitude'][$i]);
                $gpslons[$i] = explode('/', $this->data['GPS']['GPSLongitude'][$i]);
            }
            // convert
            $gpsFromExif['lon'] = ((float)$gpslons[0][0] / (float)$gpslons[0][1]) + (((float)$gpslons[1][0] / (float)$gpslons[1][1]) / 60.0) + (((float)$gpslons[2][0] / (float)$gpslons[2][1]) / 3600.0);
            $gpsFromExif['lat'] = ((float)$gpslats[0][0] / (float)$gpslats[0][1]) + (((float)$gpslats[1][0] / (float)$gpslats[1][1]) / 60.0) + (((float)$gpslats[2][0] / (float)$gpslats[2][1]) / 3600.0);
            // reference
            if (isset($this->data['GPS']['GPSLongitudeRef']) && ($this->data['GPS']['GPSLongitudeRef'] == 'W')) {
                $gpsFromExif['lon'] *= -1;
            }
            if (isset($this->data['GPS']['GPSLatitudeRef']) && ($this->data['GPS']['GPSLatitudeRef'] == 'S')) {
                $gpsFromExif['lat'] *= -1;
            }

            return [
                'type' => 'Point',
                'coordinates' => [$gpsFromExif['lon'], $gpsFromExif['lat']],
            ];
        } else {
            return [];
        }
    }

    private function _readExif(): array
    {
        // reading exif and parse data (remove unknown char-set)
        switch (@exif_imagetype($this->filepath)) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_TIFF_II:
            case IMAGETYPE_TIFF_MM:
                // thumbnail
                $this->embedded_thumbnail = @exif_thumbnail($this->filepath);
                // exif
                $meta = exif_read_data($this->filepath, 0, true);

                if ($meta !== false) {
                    // formatting
                    $metaPARSED = json_encode($meta, JSON_PARTIAL_OUTPUT_ON_ERROR);
                    return json_decode($metaPARSED, true);
                }
                // no break
            default:
                return [];
        }
    }

    public static function imageResize(string $filepath, string $mime, int $maxWidth, int $maxHeight): string
    {
        // read & create GD
        if (is_readable($filepath)) {
            $GD = @imagecreatefromstring(file_get_contents($filepath));
        } else {
            return '';
        }

        // GD error
        if (empty($GD)) {
            return '';
        }

        // resize process
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
        $imgNEW = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($imgNEW, $GD, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($GD), imagesy($GD));

        // output
        $result = false;
        ob_start();
        switch ($mime) {
            case 'image/gif':
                $result = imagegif($imgNEW, null);
                break;
            case 'image/png':
                $result = imagepng($imgNEW, null, 6);
                break;
            case 'image/bmp':
            case 'image/wbmp':
                $result = imagewbmp($imgNEW, null);
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $result = imagejpeg($imgNEW, null, 75);
                break;
            default:
                $result = false;
        }
        $binary = ob_get_clean();

        $res = '';
        if ($result) {
            $res = $binary;
        }

        return $res;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function __destruct()
    {
        // delete if this is temporary file
        if ($this->is_temp_file) {
            unlink($this->filepath);
        }
    }
}
