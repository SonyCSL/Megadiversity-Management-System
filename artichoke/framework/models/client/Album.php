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

class Album extends \artichoke\framework\abstracts\MariadbBase
{
    const READABLE = 4;
    const EDITABLE = 2;
    const UPLOADABLE = 1;
    const INVALID = 0;
    const CREATE = 1;
    const EXIST = 2;

    private $_id;
    private $state = self::INVALID;
    private $info = null;
    private $existance;

    public function __construct(int $album_id = null)
    {
        parent::__construct();

        // instance set for CREATE NEW or READ EXIST
        if (empty($album_id)) {
            // for create new album
            $this->state = self::CREATE;
        } else {
            // exist
            $this->_id = $album_id;
            $this->state = self::EXIST;
            $this->info = $this->Q('SELECT * FROM album INNER JOIN user ON album.owner_id = user.user_id WHERE album_id = '.$album_id)->fetch_assoc();
        }

        // check
        $this->existance = isset($this->info);
    }

    public function exists(): bool
    {
        return $this->existance;
    }

    /**
     * Get information array.
     *
     * @param string $key
     *
     * @return array|string|null
     */
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
            // $key is valid but not set on $this->info as key
            return;
        }
    }

    /**
     * Get owner information.
     *
     * @return \artichoke\framework\models\client\User
     */
    public function getOwner(): \artichoke\framework\models\client\User
    {
        $oid = (int)$this->getInfo('owner_id');
        return new User($oid);
    }

    public function getTitle(): string
    {
        return (string)$this->getInfo('title');
    }

    public function getDescription(): string
    {
        return (string)$this->getInfo('description');
    }

    /**
     * Get member information.
     * in set args: returns the user is exist or not on this album (boolean)
     * ino not set: returns user list (Iterable)
     *
     * @param \artichoke\framework\models\client\User $userInstance
     *
     * @return Iterable|boolean
     */
    public function members(User $userInstance = null)
    {
        if ($userInstance instanceof User) {
            // return boolean(existance)
            $uid = $userInstance->getId();
            $result = $this->Q("SELECT * FROM album_shared_members WHERE album_id = '$this->_id' AND user_id = '$uid'")->fetch_assoc();
            return isset($result);
        } else {
            // return Iterable(member list)
            $q = $this->Q("SELECT album_shared_members.user_id FROM album_shared_members INNER JOIN user ON album_shared_members.user_id = user.user_id WHERE album_shared_members.album_id = '$this->_id'");
            while ($uid = $q->fetch_row()) {
                yield new User($uid[0]);
            }
        }
    }

    /**
     * Can the user view this album or not.
     *
     * @param \artichoke\framework\models\client\User $userInstance
     *
     * @return boolean
     */
    public function viewable(User $userInstance): bool
    {
        return $this->_grants($userInstance, self::READABLE, 'view_all');
    }

    /**
     * Can the user edit (alter configure, edit associated entry) this album or not.
     *
     * @param \artichoke\framework\models\client\User $userInstance
     *
     * @return boolean
     */
    public function editable(User $userInstance): bool
    {
        return $this->_grants($userInstance, self::EDITABLE, 'edit_all');
    }

    /**
     * Can the user upload (on web & from the user's device) to this album or not.
     *
     * @param \artichoke\framework\models\client\User $userInstance
     *
     * @return boolean
     */
    public function uploadable(User $userInstance): bool
    {
        return $this->_grants($userInstance, self::UPLOADABLE, 'upload_all');
    }

    private function _grants(User $userInstance, int $grant_num, string $grant_assoc): bool
    {
        $user = $userInstance->getInfo();

        // return true, when the conditions below,
        // 1. permission: everyone can upload
        // 2. the user is album owner
        // 3. the user is album member and the member permitted uploading
        // 4. the user has grants to upload all albums
        return (((int)$this->info['permission_others'] & $grant_num) ||
                ($userInstance->getId() === (int)$this->info['owner_id']) ||
                (((int)$this->info['permission_members'] & $grant_num) && $this->members($userInstance)) ||
                ((int)$userInstance->getInfo($grant_assoc) === parent::BOOL_TRUE));
    }
}
