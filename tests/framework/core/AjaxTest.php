<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Ajax;

class AjaxTest extends TestCase
{
    const TEST_TARGET_URL = 'https://www.test_target_url.html';
    const TEST_TARGET_METHOD = 'target_method';

    // Test for createCode
    public function test_createCode_AllNull()
    {
        $out = (new Ajax())->createCode('', '');

        $ref = <<<'AJAXJS'
    <script>
      $(document).ready(function () {
        $('#ajaxExecute').click(function () {
          $('#ajaxResult').val('Loading');
          $.ajax({
            url: '',
            type: '',
            dataType: 'json',
            headers: {
              
            },
            data: {
              
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

        $this->assertEquals($out, $ref);
    }

    public function test_createCode_CheckUrlAndMethod()
    {
        $out = (new Ajax())->createCode(self::TEST_TARGET_URL, self::TEST_TARGET_METHOD);

        $ref = <<<'AJAXJS'
    <script>
      $(document).ready(function () {
        $('#ajaxExecute').click(function () {
          $('#ajaxResult').val('Loading');
          $.ajax({
            url: 'https://www.test_target_url.html',
            type: 'target_method',
            dataType: 'json',
            headers: {
              
            },
            data: {
              
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

        $this->assertEquals($out, $ref);
    }

    public function test_createCode_CheckFormArray()
    {
        $header_array = array('test', 'array');
        $form_array = array('1st', '2nd', '3rd');

        $out = (new Ajax())->createCode(self::TEST_TARGET_URL, self::TEST_TARGET_METHOD, $header_array, $form_array);

        $ref = <<<'AJAXJS'
    <script>
      $(document).ready(function () {
        $('#ajaxExecute').click(function () {
          $('#ajaxResult').val('Loading');
          $.ajax({
            url: 'https://www.test_target_url.html',
            type: 'target_method',
            dataType: 'json',
            headers: {
              'test': $('#ajaxHeader--test').val(), 'array': $('#ajaxHeader--array').val(), 
            },
            data: {
              1st: $('#ajaxForm--1st').val(), 2nd: $('#ajaxForm--2nd').val(), 3rd: $('#ajaxForm--3rd').val(), 
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

        $this->assertEquals($out, $ref);
    }

    public function test_createCode_CheckHeaderArray()
    {
        $header_array = array('test', 'array');

        $out = (new Ajax())->createCode(self::TEST_TARGET_URL, self::TEST_TARGET_METHOD, $header_array);

        $ref = <<<'AJAXJS'
    <script>
      $(document).ready(function () {
        $('#ajaxExecute').click(function () {
          $('#ajaxResult').val('Loading');
          $.ajax({
            url: 'https://www.test_target_url.html',
            type: 'target_method',
            dataType: 'json',
            headers: {
              'test': $('#ajaxHeader--test').val(), 'array': $('#ajaxHeader--array').val(), 
            },
            data: {
              
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

        $this->assertEquals($out, $ref);
    }
}
