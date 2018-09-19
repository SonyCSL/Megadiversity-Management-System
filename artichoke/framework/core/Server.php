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

final class Server
{
    private $server_var;

    public function __construct(array $server)
    {
        $this->server_var = $server;
    }

    /**
     * Get complete url, domain name with http scheme.
     *
     * @param string $fqdn (optional, require only accessing another app.)
     *
     * @return string
     */
    public function rootURL(string $fqdn = null): string
    {
        if (!empty($this->server_var['HTTPS'])) {
            $ru = 'https://';
        } else {
            $ru = 'http://';
        }
        if (isset($fqdn)) {
            return $ru.$fqdn.'/';
        } else {
            return $ru.$this->myFQDN().'/';
        }
    }

    /**
     * Get full network name (myhost.mydomain)
     * This function returns different values on each app.
     *
     * @return string
     */
    public function myFQDN(): string
    {
        $sn = ($this->server_var['SERVER_NAME'] === '127.0.0.1') ? 'localhost' : $this->server_var['SERVER_NAME'];
        $hn = (isset($this->server_var['HTTP_HOST'])) ? $this->server_var['HTTP_HOST'] : '';
        if (strpos($hn, $sn) === false) {
            // SERVER_NAME string not be within HTTP_HOST
            // invalid HOST header received ?
            if (strpos($sn, '.') === false) {
                // has no subdomain or tld (e.g. 'localdomain')
                return php_uname('n');
            } else {
                // has any level of domain (e.g. 'api.syneco.com')
                return $sn;
            }
        } else {
            // SERVER_NAME string be within HTTP_HOST
            return $hn;
        }
    }

    /**
     * Send page redirect header.
     * In case with no argument, redirecting to root (top page).
     * The return value is useless in actual use case (only for test).
     *
     * @param string $page : based on current fqdn (root URL)
     *
     * @return string
     */
    public function redirect(string $page = null): string
    {
        $str = '';
        if (isset($page)) {
            $page = trim($page, ' /');
            $str = $this->rootURL().$page;
        } else {
            $str = $this->rootURL();
        }

        header('Location: '.$str);
        return $str;
    }

    /**
     * Redirect to the other fqdn.
     * Use at jumping inter-domain.
     * e.g. redirection "http://blog.mydomain/ -> http://shop.mydomain/"
     * At framework, this function use on after building an application.
     * The return value is useless in actual use case (only for test).
     *
     * @param string $fqdn : the different fqdn from current one
     *
     * @return string
     */
    public function switchFQDN(string $fqdn = null): string
    {
        $str = '';
        if (isset($fqdn)) {
            $str = $this->rootURL($fqdn);
        } else {
            $str = $this->rootURL();
        }

        header('Location: '.$str);
        return $str;
    }

    /**
     * Send HTTP status code from framework.
     * The return value is useless in actual use case (only for test).
     *
     * @see https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     *
     * @param integer $code : HTTP status code, see above. (not fully supporting)
     *
     * @return string code with full message
     */
    public function sendHttpStatusCode(int $code): string
    {
        $str = '';
        $status = [
            200 => '200 OK',
            201 => '201 Created',
            300 => '300 Multiple Choices',
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            406 => '406 Not Acceptable',
            408 => '408 Request Timeout',
            409 => '409 Conflict',
            412 => '412 Precondition Failed',
            413 => '413 Request Entity Too Large',
            416 => '416 Requested Range Not Satisfiable',
            417 => '417 Expectation Failed',
            418 => '418 I\'m a teapot',
            500 => '500 Internal Server Error',
            501 => '501 Not Implemented',
            503 => '503 Service Unavailable',
        ];

        if (!empty($status[$code])) {
            $str = $this->server_var['SERVER_PROTOCOL'].' '.$status[$code];
        } else {
            $str = $this->server_var['SERVER_PROTOCOL'].' '.$status[500];
        }

        header($str);
        return $str;
    }

    /**
     * Send Content-Type header.
     * Use on send resource file.
     * The return value is useless in actual use case (only for test).
     *
     * @param string $type : mime type declaration (full/short)
     *
     * @return string
     */
    public function sendMimeType(string $type): string
    {
        $str = '';
        $mimes = [
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'text/javascript',
            'txt' => 'text/plain',
            'text' => 'text/plain',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'gexf' => 'application/gexf+xml',
            'm3u8' => 'application/x-mpegURL',
            'bin' => 'application/octet-stream',
            'zip' => 'application/zip',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
            'ts' => 'video/mp2t',
        ];
        if (!empty($mimes[$type])) {
            // argument is short scheme, like 'jpg', 'text', ...
            $str = 'Content-Type: '.$mimes[$type];
        } elseif (array_search($type, $mimes) !== false) {
            // argument is full scheme, like 'image/jpg', 'text/csv', ...
            $str = 'Content-Type: '.$type;
        } else {
            // unknown mimetype
            $str = 'Content-Type: application/octet-stream';
        }

        // add charset
        if (strpos($str, 'text/') !== false) {
            $str .= '; charset=UTF-8';
        }

        // options
        header('X-Frame-Options: DENY'); # must be sent
        header('X-Content-Type-Options : nosniff'); # escape sniffing
        header('X-XSS-Protection: 1; mode=block'); # XSS protection (on browser)
        header('Cache-Control: private, no-cache, must-revalidate'); # cache option

        header($str);
        return $str;
    }

    /**
     * Verify ajax access or not.
     * Returns true if this request is from asynchronous javascript access.
     * The controller assumed to receive ajax access, should use this function
     * to check access origin.
     *
     * @return boolean
     */
    public function fromAjax(): bool
    {
        return (isset($this->server_var['HTTP_X_REQUESTED_WITH']) &&
                (strtolower($this->server_var['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));
    }

    /**
     * Get current request method with protocol.
     * The return value is combination of "HTTP" or "HTTPS" with "GET" or "POST".
     *
     * @return string
     */
    public function currentProtocol(): string
    {
        if ((int)$this->server_var['SERVER_PORT'] === 80) {
            return 'HTTP_'.$this->server_var['REQUEST_METHOD'];
        } elseif ((int)$this->server_var['SERVER_PORT'] === 443) {
            return 'HTTPS_'.$this->server_var['REQUEST_METHOD'];
        } else {
            return 'UNKNOWN';
        }
    }

    /**
     * Make download specific file.
     *
     * @param string  $data     binary
     * @param string  $filename
     * @param string  $mime
     * @param boolean $download
     *
     * @return integer sent bytes
     */
    public function sendData(string $data, string $filename = 'data.txt', string $mime = 'text', bool $download = false): int
    {
        if ($download) {
            header('Content-Type: application/force-download');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
        } else {
            $this->sendMimeType($mime);
        }

        $len = strlen($data);
        if (!empty($len)) {
            header('Content-Length: '.$len);
            echo $data;
            return $len;
        } else {
            return 0;
        }
    }

    /**
     * Get all headers on the request.
     *
     * @return array headers, array(HEADER_NAME => HEADER_VALUE)
     */
    public function getAllHeaders(): array
    {
        $h = [];
        foreach ($this->server_var as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $h[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $h;
    }

    /**
     * Get requested HTTP method.
     * Argument set: returns true or false about same value with HTTP method.
     * Argument not set: returns HTTP method string (CASE_UPPER).
     *
     * @param string $method
     *
     * @return string|bool
     */
    public function methodIs(string $method = null)
    {
        if ($method !== null) {
            return ($this->server_var['REQUEST_METHOD'] === strtoupper($method));
        } else {
            return $this->server_var['REQUEST_METHOD'];
        }
    }

    /**
     * Get request URI
     *
     * @return $_SERVER['REQUEST_URI']
     */
    public function getRequestURI()
    {
        $res = '';
        if (!empty($this->server_var['REQUEST_URI'])) {
            $res = $this->server_var['REQUEST_URI'];
        }

        return $res;
    }
}
