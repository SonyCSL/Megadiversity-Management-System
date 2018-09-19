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

use artichoke\framework\core\Session;
use artichoke\framework\core\Requests;
use artichoke\framework\core\Server;
use artichoke\framework\models\client\User;

class ApitesterController extends \artichoke\framework\abstracts\ControllerBase
{
    private $apiquery = null;
    private $acckey = null;
    private $result = null;

    public function indexAction(array $args = [])
    {
        // init
        $myName = (new Session())->getLoginName();
        $user = new User($myName);

        // my own info
        $this->set('myname', $myName);
        $this->set('mygroupname', $user->getGroupName());

        // for demonstration
        $this->set('is_analytics', 'selected');
        $this->set('is_localfile', 'checked');
        $this->set('apiquery', 'tensorflow/imagenet');
        $this->set('acckey', '');

        // running
        if ((new Requests($_REQUEST))->get('run') === 'do') {
            $this->runAction();
        }
        if (!empty($this->apiquery)) {
            $this->set('apiquery', $this->apiquery);
        }
        if (!empty($this->acckey)) {
            $this->set('acckey', $this->acckey);
        }
        if (!empty($this->result)) {
            $this->set('result', $this->result);
        }
    }

    public function runAction()
    {
        $request = new Requests($_REQUEST, $_FILES);

        $this->acckey = $request->get('acckey');
        $uri = (new Server($_SERVER))->rootURL().'api/';
        $this->apiquery = $request->get('apiquery');
        $m = $request->get('apimethod');
        $this->set('is_'.$m, 'selected');
        $uri .= $m.'/'.$this->apiquery;

        // cURL
        $curl = curl_init($uri);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['X-API-Accesskey: '.$this->acckey]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 100);
        switch ($m) {
            case 'get':
            case 'search':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
            case 'post':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->setPostAction($request));
                break;
            case 'analytics':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->setAnalyticsAction($request));
                break;
            default:
                break;
        }

        $returned_data_body = curl_exec($curl);
        $returned_content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        $this->setResult($returned_data_body, $returned_content_type);
        curl_close($curl);
    }

    private function setPostAction(Requests $request)
    {
        $filepath = $request->filepath('file');
        if ($filepath === '') {
            $postdata = [];
        } else {
            $postdata['file'] = new \CURLFile($filepath, mime_content_type($filepath), 'APITESTFILE');
        }

        return $postdata;
    }

    private function setAnalyticsAction(Requests $request)
    {
        $postdata = [];
        $targetSelect = $request->get('targetselect');

        $filepath = $request->filepath('file');
        if ($targetSelect === 'file' && $filepath !== null) {
            $postdata['file'] = new \CURLFile($filepath, mime_content_type($filepath), 'APITESTFILE');
        }
        $targetEntry = $request->get('entryid');
        if ($targetSelect === 'entryid' && $targetEntry !== null) {
            $postdata['entryid'] = $targetEntry;
        }
        $targetAlbum = $request->get('albumid');
        if ($targetSelect === 'albumid' && $targetAlbum !== null) {
            $postdata['albumid'] = $targetAlbum;
        }

        return $postdata;
    }

    private function setResult($returned_data_body, $returned_content_type)
    {

        // result
        if (is_string($returned_content_type) && strpos($returned_content_type, 'image/') !== false) {
            $this->result = '<img width="100%" src="data:'.$returned_content_type.';base64,'.base64_encode($returned_data_body).'">';
        } else {
            $d = json_decode($returned_data_body, true);
            $e = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $this->result = "<pre>\n";
            $this->result .= $e;
            $this->result .= "\n</pre>\n";
        }
    }
}
