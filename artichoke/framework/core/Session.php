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

final class Session
{
    const SESSKEY = 'ARTICHOKE_SESSION_CONTROL';

    public function init()
    {
        session_cache_expire(0);
        session_cache_limiter('nocache');
    }

    public function loggingIn($name = '')
    {
        session_start();
        session_regenerate_id(true);
        $_SESSION[self::SESSKEY]['login_state'] = true;
        $_SESSION[self::SESSKEY]['login_name'] = $name;
        $_SESSION[self::SESSKEY]['onetime_id'] = bin2hex(openssl_random_pseudo_bytes(16));
        session_commit();
    }

    public function loginStatus()
    {
        session_start();
        if (isset($_SESSION[self::SESSKEY]['login_state']) && $_SESSION[self::SESSKEY]['login_state'] === true) {
            $ret = true;
        } else {
            $ret = false;
        }
        session_commit();
        return $ret;
    }

    public function getLoginName()
    {
        session_start();
        if (isset($_SESSION[self::SESSKEY]['login_name'])) {
            $ret = $_SESSION[self::SESSKEY]['login_name'];
        } else {
            $ret = null;
        }
        session_commit();
        return $ret;
    }

    public function loggingOut()
    {
        session_start();
        unset($_SESSION[self::SESSKEY]);
        session_destroy();
    }

    public function tmpfile($data = null)
    {
        session_start();
        if (isset($data)) {
            $_SESSION[self::SESSKEY]['tmpfile'] = $data;
            $ret = strlen($data);
        } else {
            if (isset($_SESSION[self::SESSKEY]['tmpfile'])) {
                $ret = $_SESSION[self::SESSKEY]['tmpfile'];
            } else {
                $ret = null;
            }
        }
        session_commit();
        return $ret;
    }

    public function jumpAfterLogin($url = '')
    {
        $status = self::loginStatus();
        session_start();
        if ($status) {
            // logged in
            if (isset($_SESSION[self::SESSKEY]['requested_path']) &&
                !is_null($_SESSION[self::SESSKEY]['requested_path'])) {
                $ret = $_SESSION[self::SESSKEY]['requested_path'];
                $_SESSION[self::SESSKEY]['requested_path'] = null;
            } else {
                $ret = false;
            }
        } else {
            // not logged in
            $_SESSION[self::SESSKEY]['requested_path'] = $url;
            $ret = true;
        }
        session_commit();
        return $ret;
    }

    public function destruct()
    {
        session_start();
        $_SESSION = [];
        session_destroy();
    }
}
