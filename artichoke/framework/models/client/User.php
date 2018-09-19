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

namespace artichoke\framework\models\client;

class User extends \artichoke\framework\abstracts\MariadbBase
{
    const INVALID = 0;
    const CREATE = 1;
    const EXIST = 2;
    const PREPARED_SQL_HEAD = 'SELECT user_id, username, password, email, language_id, user.group_id, groupname, edit_all, view_all, upload_all FROM user INNER JOIN user_groups ON user_groups.group_id = user.group_id WHERE ';

    private $_id = 0;
    private $info = null;

    public function __construct($user_identify = null)
    {
        parent::__construct();

        // instace set for CREATE NEW or READ EXIST
        if (empty($user_identify)) {
            // for create new album
            $this->state = self::CREATE;
        } else {
            // exist
            $this->state = self::EXIST;
            // select search condition
            switch (gettype($user_identify)) {
                case 'integer':
                    // identified by user_id
                    $stmt = $this->db->prepare(self::PREPARED_SQL_HEAD.'user.user_id = ?');
                    break;
                case 'string':
                    if (strpos($user_identify, '@') === false) {
                        // identified by username
                        $stmt = $this->db->prepare(self::PREPARED_SQL_HEAD.'user.username = ?');
                    } else {
                        // identified by mail address
                        $stmt = $this->db->prepare(self::PREPARED_SQL_HEAD.'user.email = ?');
                    }
                    break;
                default:
                    $search_condition = null;
                    $stmt = false;
            }

            // get information from database
            if ($stmt !== false) {
                $stmt->bind_param('s', $user_param);
                $user_param = (string)$user_identify;
                $stmt->execute();
                $stmt->bind_result(
                    $this->info['user_id'],
                    $this->info['username'],
                    $this->info['password'],
                    $this->info['email'],
                    $this->info['language_id'],
                    $this->info['group_id'],
                    $this->info['groupname'],
                    $this->info['edit_all'],
                    $this->info['view_all'],
                    $this->info['upload_all']
                );

                if ($stmt->fetch() !== true) {
                    $this->info = null;
                }

                $stmt->close();
            } else {
                $this->info = null;
            }

            // existance
            if ($this->info !== null && isset($this->info['user_id'])) {
                $this->_id = (int)$this->info['user_id'];
            }
        }
    }

    public function exists(): bool
    {
        return isset($this->info);
    }

    public function loginAuth(string $input_userpswd): bool
    {
        // check user existance and password
        if ($this->exists() && !empty($input_userpswd)) {
            // check
            if (hash('sha256', $input_userpswd) === $this->info['password']) {
                // this verification is deprecated: will delete in future
                return true;
            } else {
                // standard
                return password_verify($input_userpswd, $this->info['password']);
            }
        } else {
            // empty passwd or unknown user
            return false;
        }
    }

    public function isAdmin(): bool
    {
        if ($this->exists()) {
            return ($this->info['groupname'] === 'Administrator');
        } else {
            return false;
        }
    }

    public function getInfo(string $key = null)
    {
        if (!($this->state & self::EXIST)) {
            // invalid id (instance) or $key is valid but not set on $this->info as key
            return;
        } elseif (empty($key)) {
            // return all array
            return $this->info;
        } elseif (isset($this->info[$key])) {
            // string
            return $this->info[$key];
        } else {
            return;
        }
    }

    public function getId(): int
    {
        return $this->_id;
    }

    public function getName(): string
    {
        return (string)$this->getInfo('username');
    }

    public function getGroupName(): string
    {
        return (string)$this->getInfo('groupname');
    }

    public function editAll(): bool
    {
        return boolval($this->getInfo('edit_all'));
    }

    public function viewAll(): bool
    {
        return boolval($this->getInfo('view_all'));
    }

    public function uploadAll(): bool
    {
        return boolval($this->getInfo('upload_all'));
    }
}
