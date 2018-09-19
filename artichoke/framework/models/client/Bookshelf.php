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

class Bookshelf extends \artichoke\framework\abstracts\MariadbBase
{
    private $user;

    public function __construct(User $user_instance)
    {
        parent::__construct();
        $this->user = $user_instance;
    }

    public function count(): int
    {
        if ($this->user->viewAll()) {
            // for Administrator or Viewer, all albums
            return (int)$this->Q("SELECT * FROM album INNER JOIN user ON album.owner_id = user.user_id")->num_rows;
        } else {
            // albums only you can view
            $user_id = $this->user->getId();
            return (int)$this->Q("SELECT DISTINCT * FROM album INNER JOIN user ON album.owner_id = user.user_id WHERE (album.album_id IN(SELECT album_id FROM album_shared_members WHERE user_id = '$user_id') AND permission_members >= 4) OR album.owner_id = '$user_id' OR permission_others >= 4")->num_rows;
        }
    }

    public function listup(int $max = 100, int $offset = 0): Iterable
    {
        // default
        $sortSelect = 'create_timestamp';
        $orderSelect = 'DESC';

        // switch each user's grant
        if ($this->user->viewAll()) {
            // for Administrator or Viewer, all albums
            $result = $this->Q("SELECT * FROM album INNER JOIN user ON album.owner_id = user.user_id ORDER BY $sortSelect $orderSelect LIMIT $offset, $max");
        } else {
            // albums only you can view
            $user_id = $this->user->getId();
            $result = $this->Q("SELECT DISTINCT * FROM album INNER JOIN user ON album.owner_id = user.user_id WHERE (album.album_id IN(SELECT album_id FROM album_shared_members WHERE user_id = '$user_id') AND permission_members >= 4) OR album.owner_id = '$user_id' OR permission_others >= 4 ORDER BY album.$sortSelect $orderSelect LIMIT $offset, $max");
        }

        // Listing
        while ($album = $result->fetch_assoc()) {
            $result_line['_id'] = (int)$album['album_id'];
            $result_line['album_hidden'] = !($album['permission_others'] & 4); // readable or not?
            $result_line['title'] = $album['title'];
            $result_line['title_short'] = (strlen($album['title']) > 14) ? mb_substr($album['title'], 0, 14).'..' : $album['title'];
            $result_line['username'] = $album['username'];
            $result_line['modified_timestamp'] = $album['modified_timestamp'];
            // sample file
            $result_line['fileCount'] = 0;
            $result_line['datastringCount'] = 0;
            $result_line['fileCountString'] = '0 files';
            $result_line['datastringCountString'] = '0 strings';
            $result_line['thumbnail_file_id'] = 0;
            yield $result_line;
        }
    }
}
