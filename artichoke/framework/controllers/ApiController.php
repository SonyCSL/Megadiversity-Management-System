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

use artichoke\framework\core\Configurator;
use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;
use artichoke\framework\util\ApiResult;
use artichoke\framework\util\GetNameSpace;
use artichoke\framework\models\entry\Data;
use artichoke\framework\models\entry\Entry;
use artichoke\framework\models\entry\Metadata;
use artichoke\framework\models\client\Device;

class ApiController extends \artichoke\framework\abstracts\ControllerBase
{
    private $headers;
    private $server;
    private $result;
    private $device;
    private $respond_at_destructor = true;

    const IS_POST_ACTION_NOT_SKIP = 0;

    public function __construct(Device $device = null)
    {
        $this->server = new Server($_SERVER);

        // headers
        mb_internal_encoding('UTF-8');
        header('Connection: Close');
        header('Access-Control-Allow-Origin:*');

        // Get All Headers
        $this->headers = $this->server->getallheaders();

        // Authentication with unique header: X-API-Accesskey
        if (empty($this->headers['X-Api-Accesskey'])) {
            // If accesskey is not set
            $this->result = new ApiResult(ApiResult::ACCESSKEY_ISNT_SET);
            return;
        }

        // device class
        if ($device === null) {
            $this->device = new Device((string)$this->headers['X-Api-Accesskey']);
        } else {
            $this->device = $device;
        }

        // accesskey is not registared
        if (!$this->device->exists()) {
            $this->result = new ApiResult(ApiResult::INVALID_ACCESSKEY);
            return;
        }

        // If the device set to disabled by user setting
        if (!$this->device->available()) {
            $this->result = new ApiResult(ApiResult::DEVICE_DISABLED);
            return;
        }
    }

    /**
     * All of calls about the API methods that not implemented will be redirected to here.
     *
     * @param array $args
     */
    public function indexAction(array $args = [])
    {
        $this->result = new ApiResult(ApiResult::ENDPOINT_UNAVAILABLE);
        $this->result->setDetail($args);
    }

    /**
     * Get File or Document API
     *
     * @param array $args
     */
    public function getAction(array $args = [])
    {
        // already set result, skip this action
        if (isset($this->result)) {
            return;
        }

        // deny access except get method
        if (!$this->server->methodIs('GET')) {
            $this->result = new ApiResult(ApiResult::HTTP_METHOD_MISMATCH);
            return;
        }

        // dispatch
        $endpoint = array_shift($args);
        if (isset($endpoint)) {
            if ($endpoint === 'file') {
                if (!empty($args)) {
                    $this->respond_at_destructor = false;
                    $imgOutput = new FileController();
                    $imgOutput->indexAction($args);
                } else {
                    $this->result = new ApiResult(ApiResult::ERROR_INTERNAL);
                    $this->result->setStatus('File ID is required');
                }
            } else {
                $this->result = new ApiResult(ApiResult::ENDPOINT_UNAVAILABLE);
                $this->result->setDetail($args);
            }
        } else {
            // no params error
            $this->result = new ApiResult(ApiResult::ENDPOINT_UNAVAILABLE);
        }
    }

    /**
     * File or Datastring post (create) API
     *
     * @param array $args
     */
    public function postAction(array $args = [])
    {
        // already set result, skip this action
        if (isset($this->result)) {
            return;
        }

        // requested parameter getter
        $request = new Requests($_REQUEST, $_FILES);

        $skip_param = $this->getPostActionSkipParam($request, $this->headers, $this->server->methodIs('POST'));

        if ($skip_param !== self::IS_POST_ACTION_NOT_SKIP) {
            $this->result = new ApiResult($skip_param);
            return;
        }

        //set geo information
        $geo_data = $this->setGeoInformation($request, $this->device->getGeoJsonArray());
        $geoJsonArray = $geo_data[0];
        $register_geojson_to_document = $geo_data[1];

        // data register for uploading
        $uploader = new Entry();

        // album & device ID
        $uploader->setAlbumId($this->device->getAlbumId());
        $uploader->setDeviceId($this->device->getId());

        // if flag is true, add GeoJSON to the new document
        if ($register_geojson_to_document) {
            $uploader->setGeoJsonArray($geoJsonArray);
        }

        $uploader = $this->setUploaderFromRequests($request, $uploader);

        $dataInstance = $this->getDataInstance($this->headers, $request);
        if (isset($dataInstance)) {
            $json_error = $dataInstance->getParseError();
            if (empty($json_error)) {
                // no error occured at json datastring, set at uploader as one of arg
                $uploader->setData($dataInstance);
            } else {
                // json datastring is invalid
                $this->result = new ApiResult(ApiResult::DATASTRING_JSON_PARSE_ERROR);
                $this->result->setStatus($json_error);
                return;
            }
        }

        // optional data with uploading
        $uploader->setUploadMethod($this->server->currentProtocol());

        // uploaded file check: switching as FILE or DATASTRING
        $uploader = $this->setUploaderFromFile($request, $uploader);

        // uploading
        // SUCCESS: [true, <string> new_entry_id, <\ArrayObject> inserted_document]
        // FAILURE: [false, <string> error_message]
        $result = $uploader->create();

        if ($result[0]) {
            // success
            $this->result = new ApiResult(ApiResult::POST_SUCCESS);
            // detail select
            if (isset($result[2])) {
                $body = (array)$result[2]; // \ArrayObject to array
                if (isset($body['thumbnailB64'])) {
                    unset($body['thumbnailB64']);
                }
                $this->result->setDetail($body);
            } else {
                $this->result->setDetail(['_id' => $result[1]]);
            }
        } else {
            // fail
            $this->result = new ApiResult(ApiResult::ERROR_INTERNAL);
            $this->result->setStatus($result[1]);
        }
    }

    private function getPostActionSkipParam(Requests $request, array $headers, $method): int
    {
        $res = self::IS_POST_ACTION_NOT_SKIP;
        // deny access except post method
        if ($method === false) {
            return ApiResult::HTTP_METHOD_MISMATCH;
        }

        if (empty($headers['Content-Type'])) {
            $res = ApiResult::CONTENT_TYPE_UNKNOWN;
        } else {
            $keys_all = $request->get();

            switch ($headers['Content-Type']) {
                case 'application/json':
                case 'text/json':
                    break;
                case 'application/x-www-form-urlencoded':
                    if (empty($keys_all['datastring']) && empty($keys_all['data'])) {
                        $res = ApiResult::POSTED_NOTHING;
                    }
                    break;
                default:
                    if ($request->filepath('file') === '' && empty($keys_all['datastring']) && empty($keys_all['data'])) {
                        $res = ApiResult::REQUIRED_FIELD_ISNT_SET;
                    }
                    break;
            }
        }

        return $res;
    }

    /**
     * Set Geo Information
     *
     * @param Requests $request
     *
     * @return array $res $res[0] : geoJsonArray, $res[1] : register_geojson_to_document
     */
    private function setGeoInformation(Requests $request, array $geoJsonArrayOrg): array
    {
        // geo information
        $geoJsonArray = $geoJsonArrayOrg;
        $register_geojson_to_document = false;

        // location (position) & flag for containing entry document or not
        if ($request->get('location') !== null) {
            $position = explode(',', (string)$request->get('location')); // expects string "longitude, latitude"

            if (isset($position[1])) {
                // valid format (longitude,latitude)
                $geoJsonArray = array_merge($geoJsonArray, ['coordinates' => [(float)$position[0], (float)$position[1]]]);
                $register_geojson_to_document = true;
            } elseif ($position[0] === 'default' || $position[0] === 'on' || $position[0] === 'true') {
                // 'USE DEFAULT LOCATION' flag
                $register_geojson_to_document = true;
            }
        }

        // any other geo information
        foreach ([
            ['altitude', 'float'],
            ['ground_height', 'integer'],
            ['place', 'string'],
            ['local_position', 'string'],
        ] as $geo_key) {
            // if geo-key exist, add to $geoJsonArray, toggle flag true
            $geo_value = $request->get($geo_key[0]); // copy from $request to var
            if ($geo_value !== null) {
                settype($geo_value, $geo_key[1]); // convert type
                $geoJsonArray = array_merge($geoJsonArray, [$geo_key[0] => $geo_value]); // add to $geoJsonArray
                $register_geojson_to_document = true; // flag (make true at least one keys detected)
            }
        }

        return [$geoJsonArray, $register_geojson_to_document];
    }

    /**
     * Set Geo Information
     *
     * @param Requests $request
     *
     * @return Entry $res
     */
    private function setUploaderFromRequests(Requests $request, Entry $uploader): Entry
    {
        $res = $uploader;

        // datetime (user specified / local time)
        if ($request->get('datetime') !== null) {
            $res->setDatetime($request->get('datetime'));
        }

        // timezone (optional, not require)
        if ($request->get('timezone') !== null) {
            $res->setTimezone((float)$request->get('timezone'));
        }

        // tags
        if ($request->get('tag') !== null) {
            $res->setTags((string)$request->get('tag'));
        } elseif ($request->get('tags') !== null) {
            $res->setTags((string)$request->get('tags'));
        }

        // public flag
        // allow using this data for statistics or analytics
        if ($request->get('public') !== null) {
            $res->set('public', (bool)$request->get('public'));
        }

        // comment
        if ($request->get('comment') !== null) {
            $res->setComment($request->get('comment'));
        }

        return $res;
    }

    /**
     * Set Geo Information
     *
     * @param array    $headers
     * @param Requests $request
     *
     * @return Data $dataInstance
     */
    private function getDataInstance(array $headers, Requests $request)
    {
        if (!empty($headers['Content-Type'])) {
            if (($headers['Content-Type'] === 'application/json') ||
                ($headers['Content-Type'] === 'text/json')
            ) {
                $raw_datastring = file_get_contents('php://input');
            }
        }

        $dataInstance = null;
        // datastring check (which type ?)
        if (isset($raw_datastring)) {
            // datastring is whole of the requested body
            $dataInstance = new Data((string)$raw_datastring);
        } elseif ($request->get('datastring') !== null) {
            // datastrings is associated with 'datastrings' key on multipart/form-data
            $dataInstance = new Data((string)$request->get('datastring'));
        } elseif ($request->get('data') !== null) {
            // 'data' field is alternative key for 'datastring'
            $dataInstance = new Data((string)$request->get('data'));
        }

        return $dataInstance;
    }

    /**
     * Set Geo Information
     *
     * @param Requests $request
     *
     * @return Entry $res
     */
    private function setUploaderFromFile(Requests $request, Entry $uploader): Entry
    {
        $res = $uploader;

        $filepath = $request->filepath('file');
        if (!empty($filepath)) {
            // only fs.files
            $res->setFile($filepath, $request->filename('file'));
            $metadataInstance = new Metadata($filepath);
            $res->setThumbnail($metadataInstance->getThumbnail());
            $res->setMetadata($metadataInstance->toArray());
            $res->setGeoJsonArray($metadataInstance->getGeoJsonArray());
            $res->setDatetime($metadataInstance->getDatetime());
            $res->addTags($metadataInstance->getTags());
            $additionalComment = $metadataInstance->getDescription();
            if (!empty($additionalComment)) {
                $res->setComment("\n".$additionalComment);
            }
        }

        return $res;
    }

    /**
     * Search API
     *
     * @param array $args
     */
    public function searchAction(array $args = [])
    {
        // already set result, skip this action
        if (isset($this->result)) {
            return;
        }

        // deny access except get method
        if (!$this->server->methodIs('GET')) {
            $this->result = new ApiResult(ApiResult::HTTP_METHOD_MISMATCH);
            return;
        }

        // WIP
        // @todo make Syneco search model standard
        $this->result = new ApiResult(ApiResult::ENDPOINT_UNAVAILABLE);

        $status_in = ($args === [] ? '' : $args[0]);
        $this->result->setStatus($status_in);
    }

    /**
     * Analytics API
     *
     * @param array $args
     */
    public function analyticsAction(array $args = [])
    {
        // already set result, skip this action
        if (isset($this->result)) {
            return;
        }

        // deny access except get method
        if (!$this->server->methodIs('POST')) {
            $this->result = new ApiResult(ApiResult::HTTP_METHOD_MISMATCH);
            return;
        }

        // what is requested model
        $c = count($args);
        if ($c > 0) {
            $args[$c - 1] = ucfirst($args[$c - 1]); // CASE_UPPER at first char of model name
        }
        $modelClass = implode('\\', $args);
        $analyticsModelNameApp = (new GetNameSpace())->getModelsAnalyticsModel((new Configurator())->read('app_dir'), $modelClass);
        $analyticsModelNameFW = (new GetNameSpace())->getFrameworkModelsAnalyticsModel($modelClass);

        // %% COMMON OPTION %%
        // n     : a number of suggested items (default = 1)
        // order : order of suggested items (default = descend)
        $request = new Requests($_REQUEST, $_FILES);
        $n = ($request->get('n') !== null) ? (int)$request->get('n') : 1;
        $order = ($request->get('order') !== null) ? (int)$request->get('order') : -1;

        if (class_exists($analyticsModelNameApp)) {
            $analyticsModel = new $analyticsModelNameApp($n, $order);
        } elseif (class_exists($analyticsModelNameFW)) {
            $analyticsModel = new $analyticsModelNameFW($n, $order);
        } else {
            // if not found model
            $this->result = new ApiResult(ApiResult::ENDPOINT_UNAVAILABLE);
            return;
        }

        if (!$analyticsModel->setParams($request->filepath('file'))) {
            // Parameter error (not satisfied)
            $this->result = new ApiResult(ApiResult::ERROR_INTERNAL);
            $this->setStatus('Model "'.$modelClass.'" requires '.$analyticsModel->requiredParamsWithString().' for param(s).');
        } else {
            // Running the model
            $this->result = new ApiResult(ApiResult::GET_SUCCESS);
            $this->setDetail($analyticsModel->run());
        }
    }

    public function __destruct()
    {
        // output only when as JSON
        if ($this->respond_at_destructor) {
            // if ApiResult is not set
            if (!isset($this->result)) {
                $this->result = new ApiResult(ApiResult::ERROR_INTERNAL);
            }

            // output!
            $this->server->sendMimeType('json'); // Content-Type
            $this->server->sendHttpStatusCode($this->result->getHttpStatusCode()); // HTTP Status Code
            echo (string)$this->result; // echo result JSON (use ApiResult::__toString() for echo)
        }
    }
}
