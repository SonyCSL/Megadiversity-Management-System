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

namespace artichoke\framework\core;

final class Requests
{
    private $_request_copy;
    private $_files_uploaded;
    private $_files_error;

    public function __construct(array $SUPERGLOBAL_REQUEST, array $SUPERGLOBAL_FILES = [])
    {
        // copy to private member variables from superglobals
        // all of keys will be converted to CASE_LOWER
        $this->_request_copy = array_change_key_case($SUPERGLOBAL_REQUEST);

        // preprocess for $_FILES
        foreach ($SUPERGLOBAL_FILES as $form_key => $f) {
            $tmp_name_str
              = (isset($SUPERGLOBAL_FILES[$form_key]['tmp_name'])) ? $SUPERGLOBAL_FILES[$form_key]['tmp_name'] : '';

            if (isset($f['error']) && $f['error'] !== 0) {
                $this->setFileError($tmp_name_str, $form_key, self::_errMessage($f['error']));
            } elseif (isset($f['size']) && $f['size'] === 0) {
                $this->setFileError($tmp_name_str, $form_key, 'The uploaded file is empty');
            } else {
                $this->_files_uploaded[$form_key]['name']
                  = isset($SUPERGLOBAL_FILES[$form_key]['name']) ? $SUPERGLOBAL_FILES[$form_key]['name'] : '';
                $this->_files_uploaded[$form_key]['tmp_name'] = $tmp_name_str;
            }
        }
    }

    public function get_request(): array
    {
        return $this->_request_copy;
    }

    public function get_files_uploaded(): array
    {
        return $this->_files_uploaded;
    }

    public function get_files_error(): array
    {
        return $this->_files_error;
    }

    public function get(string $key = '', bool $safe = true)
    {
        if ($key === '') {
            return $this->_request_copy;
        } elseif (count($this->_request_copy) === 0) {
            return;
        } else {
            return $this->getRequestStr($key, $safe);
        }
    }

    /**
     * Uploaded filename.
     * If no file uploaded associated with $key,
     * this function returns empty string.
     * To check existance of this file, use empty() function.
     *
     * @param string $key
     *
     * @return string filename
     */
    public function filename(string $key): string
    {
        if (!empty($this->_files_uploaded[$key]['name'])) {
            return $this->_files_uploaded[$key]['name'];
        } else {
            return '';
        }
    }

    /**
     * Temporary filepath of uploaded file.
     * If no file uploaded associated with $key,
     * this function returns empty string.
     * To check existance of this file, use empty() function.
     *
     * @param string $key
     *
     * @return string
     */
    public function filepath(string $key): string
    {
        if (!empty($this->_files_uploaded[$key]['tmp_name'])) {
            return $this->_files_uploaded[$key]['tmp_name'];
        } else {
            return '';
        }
    }

    /**
     * Get error detail when failed file uploading.
     * This function returns empty string in case with no errors,
     * so use empty() function to make sure of the error.
     *
     * @param string $key
     *
     * @return string detail string
     */
    public function fileError(string $key): string
    {
        if (!empty($this->_files_error[$key]['reason'])) {
            return $this->_files_error[$key]['reason'];
        } else {
            return '';
        }
    }

    private function setFileError(string $tmp_name_str, string $form_key, string $message)
    {
        $this->_files_error[$form_key]['tmp_name'] = $tmp_name_str;
        $this->_files_error[$form_key]['reason'] = $message;
    }

    /**
     * Returns upload error string (detail)
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @param integer $code : see above link
     *
     * @return string detail message about upload error
     */
    private static function _errMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = 'File upload stopped by extension';
                break;
            default:
                $message = 'Unknown upload error';
                break;
        }
        return $message;
    }

    private function getRequestStr(string $key, bool $safe)
    {
        $key = strtolower($key);
        if (isset($this->_request_copy[$key]) && $this->_request_copy[$key] !== '') {
            if (($safe === true) && is_string($this->_request_copy[$key])) {
                $result_str_tags_removed = strip_tags($this->_request_copy[$key]); # remove HTML & PHP tags
                // json eval
                $json_try = json_decode($result_str_tags_removed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_try)) {
                    // is json string
                    $result_str = $result_str_tags_removed;
                } else {
                    // is not json, delete quotes
                    $result_str = htmlspecialchars($result_str_tags_removed, ENT_QUOTES, 'UTF-8');
                }
            } else {
                $result_str = $this->_request_copy[$key];
            }
            return $result_str;
        } else {
            return;
        }
    }
}
