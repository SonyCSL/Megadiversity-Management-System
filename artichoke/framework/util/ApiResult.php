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

namespace artichoke\framework\util;

class ApiResult
{
    const GET_SUCCESS = 0;
    const POST_SUCCESS = 1;
    const ERROR_INTERNAL = 2;
    const ACCESSKEY_ISNT_SET = 3;
    const INVALID_ACCESSKEY = 4;
    const DEVICE_DISABLED = 5;
    const HTTP_METHOD_MISMATCH = 6;
    const ENDPOINT_UNAVAILABLE = 7;
    const CONTENT_TYPE_UNKNOWN = 8;
    const POSTED_NOTHING = 9;
    const REQUIRED_FIELD_ISNT_SET = 10;
    const DATASTRING_JSON_PARSE_ERROR = 11;

    // [message, HttpStatusCode]
    private $resultMessage = [
        ['Success', 200],
        ['Success', 201],
        ['Internal model error occurred', 500],
        ['X-Api-Accesskey header is not set', 401],
        ['Invalid X-Api-Accesskey', 403],
        ['Device is set as disabled', 304],
        ['HTTP method is not consistent with requested REST-API endpoint', 405],
        ['Requested API endpoint is not implemented', 501],
        ['Content-Type header is not set', 406],
        ['No any valid content is included in the request', 406],
        ['At least 1 field required either DATA, DATASTRING or FILE', 406],
        ['Datastring is not valid JSON style document (parse error)', 406],
    ];
    private $resultJson = [];
    private $resultCode = self::ERROR_INTERNAL;

    public function __construct(int $code)
    {
        $this->setCode($code);
    }

    public function getAllParams()
    {
        return array(
            'resultJson' => $this->resultJson,
            'resultCode' => $this->resultCode,
        );
    }

    /**
     * Set result code.
     * Needless to use expressly (will be called at self::__construct() at every instances)
     *
     * @param integer $code
     */
    public function setCode(int $code)
    {
        $this->resultCode = $code;
        if (isset($this->resultMessage[$code])) {
            // Expected result code:
            $this->resultJson = [
                'code' => $code,
                'result' => $this->resultMessage[$code][0],
            ];
        } else {
            // Unknown result code:
            $this->resultJson = [
                'code' => -1,
                'result' => 'Unexpected error occured',
            ];
            $this->setStatus('Code: '.$code);
        }
    }

    /**
     * Set framework status.
     * Typically: Additional information for any errors on database, internal model, or server.
     *
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->resultJson['status'] = $status;
    }

    /**
     * Set main data for main result responce.
     *
     *
     * @param array $detail
     */
    public function setDetail(array $detail)
    {
        $this->resultJson['detail'] = $detail;
    }

    /**
     * Get adequate response code to the result.
     *
     * @return integer
     */
    public function getHttpStatusCode(): int
    {
        return (int)$this->resultMessage[$this->resultCode][1];
    }

    public function toArray(): array
    {
        return $this->resultJson;
    }

    public function toJson(): string
    {
        return json_encode($this->resultJson, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
