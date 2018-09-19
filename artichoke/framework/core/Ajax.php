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

final class Ajax
{
    private static $ajaxjs = <<<'AJAXJS'
    <script>
      $(document).ready(function () {
        $('#ajaxExecute').click(function () {
          $('#ajaxResult').val('Loading');
          $.ajax({
            url: '<TARGET_URL>',
            type: '<TARGET_METHOD>',
            dataType: 'json',
            headers: {
              <TARGET_HEADERS>
            },
            data: {
              <TARGET_FORMS>
            },
          })
          .done(function () {
            var detail = JSON.stringify(arguments, null, "  ");
            $('#ajaxResult').val('Success');
            $('#ajaxReturn').html(detail);
          })
          .fail(function () {
            var detail = JSON.stringify(arguments, null, "  ");
            $('#ajaxResult').val('Failure');
            $('#ajaxReturn').html(detail);
          });
        });
      });
    </script>
AJAXJS;

    public static function createCode($url, $method, array $headerArray = [], array $formArray = [])
    {
        $returnJS = self::$ajaxjs;
        $returnJS = str_replace('<TARGET_URL>', $url, $returnJS);
        $returnJS = str_replace('<TARGET_METHOD>', $method, $returnJS);

        $headers = "";
        foreach ($headerArray as $hd) {
            $headers .= "'".$hd."': \$('#ajaxHeader--".$hd."').val(), ";
        }
        $returnJS = str_replace('<TARGET_HEADERS>', $headers, $returnJS);

        $forms = "";
        foreach ($formArray as $dk) {
            $forms .= $dk.": \$('#ajaxForm--".$dk."').val(), ";
        }
        return str_replace('<TARGET_FORMS>', $forms, $returnJS);
    }
}
