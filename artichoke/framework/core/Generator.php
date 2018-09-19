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

use artichoke\framework\util\GetPaths;
use artichoke\framework\util\GetNameSpace;

final class Generator
{
    private $viewRoot;
    private $pagePath;
    private $fileStream;
    private $renderStream;

    const RENDERSTREAM_SUFFIX_START_CHAR_NUM = 3;
    const RENDERSTREAM_SUFFIX_END_CHAR_NUM = -1;

    public function __construct($page = 'index')
    {
        $this->reset($page);
    }

    public function getAllParams()
    {
        return array(
            'viewRoot' => $this->viewRoot,
            'pagePath' => $this->pagePath,
            'fileStream' => $this->fileStream,
            'renderStream' => $this->renderStream,
        );
    }

    public function run($tags = [], $fTags = [], $option = null)
    {
        // 0. Read HTML Template
        $this->fileStream = file_get_contents($this->pagePath);
        if ($option === 'template_only') {
            return $this->fileStream;
        }
        // 1. Bind Files like HTML (from /views/resources)
        $binder_out = $this->binder($this->viewRoot, $this->fileStream);
        // 2. apply environmental constants
        $this->renderStream = $this->constSetter($binder_out);
        if ($option === 'template_binded') {
            return $this->renderStream;
        }
        // 3. Foreach processing
        $foreacher_out = $this->foreacher($fTags, $this->renderStream);
        // 4. Switch processing
        $switcher_out = $this->switcher($tags, $foreacher_out);
        // 5. Replace variable tags
        $replacer_out = $this->replacer($tags, $switcher_out);
        // 6. Replace ajax tag
        $this->renderStream = $this->ajaxApply($replacer_out);
        // Output!
        return $this->renderStream;
    }

    public function reset($page = 'index')
    {
        // Looking for html template
        $configurator = new Configurator();
        $root_dir = $configurator->read('system_root');
        $app_dir = $configurator->read('app_dir');

        $get_paths = new GetPaths();

        $this->setViewRoot($root_dir, $app_dir, $get_paths);
        $this->setPagePath($page, $root_dir, $get_paths);
    }

    private function setViewRoot($root_dir, $app_dir, GetPaths $get_paths)
    {
        if (is_string($app_dir)) {
            $this->viewRoot = $get_paths->getAppViewPath($root_dir, $app_dir);
        } else {
            $this->viewRoot = $get_paths->getFrameworkViewPath($root_dir);
        }
    }

    private function setPagePath($page, string $root_dir, GetPaths $get_paths)
    {
        $page = trim($page, '/');
        $templateRoot = $get_paths->getTemplatePath($this->viewRoot).$page;

        $this->pagePath = $get_paths->genHtmlFileName($templateRoot);

        if (!is_readable($this->pagePath)) {
            // Retry in next directory
            $new_path = $get_paths->getFilePathToIndexHtml($templateRoot);

            // If not found target page (2)
            if (!is_readable($new_path)) {
                $exception_class_name = (new GetNameSpace())->getNameSpaceToExceptionController();
                $error = new $exception_class_name();
                $error->indexAction([3, 'HTML template "'.$page.'" is not found in view section', null, null, null, 500]);
                $new_path = $get_paths->getFilePathToExceptionHtml($root_dir);
            }

            $this->pagePath = $new_path;
        }
    }

    private function binder(string $view_root, string $fileStream)
    {
        $res = '';
        $pizza = explode('{Bind$', $fileStream);
        foreach ($pizza as $num => $piece) {
            // Only first piece
            if ($num == 0) {
                $res = $piece;
                continue;
            }
            // After second piece
            $pan = explode('}', $piece, 2);
            // Detect extension
            $filename = $pan[0];
            $ext = substr($filename, strrpos($pan[0], '.') + 1);

            $include_tag = $this->setIncludeTagData($ext, $filename);

            // Rendering
            $rendering_file_path = (new GetPaths())->searchRenderFile($view_root, $filename);

            if ($rendering_file_path !== '') {
                $res .= $include_tag['Head'].file_get_contents($rendering_file_path).$include_tag['Tail'].$pan[1];
            } else {
                $res .= $include_tag['notfound'].$pan[1];
            }
        }

        return $res;
    }

    // Used in binder
    private function setIncludeTagData(string $file_type, string $filename): array
    {
        switch ($file_type) {
            case 'js':
                $Head = "<script type=\"text/javascript\">\n";
                $notfound = "<!-- Javascript \"".$filename."\" not found -->\n";
                $Tail = "\n</script>\n";
                break;
            case 'css':
                $Head = "<style type=\"text/css\">\n";
                $notfound = "<!-- CSS \"".$filename."\" not found -->\n";
                $Tail = "\n</style>\n";
                break;
            case 'htm':
            case 'html':
                $Head = "";
                $notfound = "<!-- HTML \"".$filename."\" not found -->\n";
                $Tail = "";
                break;
            default:
                $Head = "";
                $notfound = "<!-- Resource \"".$filename."\" not found -->\n";
                $Tail = "";
                break;
        }
        return array('Head' => $Head, 'notfound' => $notfound, 'Tail' => $Tail);
    }

    private function foreacher($pageArrays, string $stream): string
    {
        // Look up tag pair
        $fDetectedStrings = $this->lookUpRenderStreamTagPair($stream);

        // Replace
        $fReplaceStrings = [];
        // part
        foreach ($fDetectedStrings as $num => $matchstr) {
            $fReplaceStrings[$num] = '';
            // search foreach-var from detected part
            $c = preg_match_all('/\{F\$.+?\}/', $matchstr, $hitvar, PREG_PATTERN_ORDER);
            if ($c === false || $c === 0) {
                continue;
            }
            $fReplaceStrings[$num] = $this->setReplaceStrings($pageArrays, $matchstr, $hitvar[0]);
        }

        $res = '';
        // Replace
        $res = str_replace($fDetectedStrings, $fReplaceStrings, $stream);
        // Delete undefined Tags
        $res = preg_replace('/\{F\$.*\}/', '', $res);
        $res = str_replace('{Foreach}', '', $res);
        return str_replace('{/Foreach}', '', $res);
    }

    //Used in foreacher
    private function lookUpRenderStreamTagPair(string $renderStream): array
    {
        $fsOffset = 0;
        $feOffset = 0;
        $fDetectedStrings = [];

        $tag_length = strlen('{/Foreach}');

        while (true) {
            $fStart = stripos($renderStream, '{Foreach}', $fsOffset);
            $fEnd = stripos($renderStream, '{/Foreach}', $feOffset);
            if ($fStart !== false && $fEnd !== false) {
                $fDetectedStrings[] = substr($renderStream, ($tag_length + $fStart), ($fEnd - $fStart - $tag_length));
                $fsOffset = $tag_length + $fEnd;
                $feOffset = $tag_length + $fEnd;
            } else {
                break;
            }
        }

        return $fDetectedStrings;
    }

    //Used in foreacher
    private function setReplaceStrings(array $pageArrays, string $matchstr, array $hitvar): string
    {
        $res = '';
        // index count
        $fbreak = false;
        for ($i = 0; ; $i++) {
            // each vars
            $thispart = $matchstr;
            foreach ($hitvar as $arvars) {
                $thiskey = substr(
                    $arvars,
                    self::RENDERSTREAM_SUFFIX_START_CHAR_NUM,
                    self::RENDERSTREAM_SUFFIX_END_CHAR_NUM
                );
                if (isset($pageArrays[$thiskey]) && isset($pageArrays[$thiskey][$i])) {
                    $thispart = str_replace($arvars, $pageArrays[$thiskey][$i], $thispart);
                } else {
                    $fbreak = true;
                }
            }
            if ($fbreak) {
                break;
            }
            $res .= $thispart;
        }

        return $res;
    }

    private function switcher($tags, string $stream): string
    {
        $res = $stream;

        $pattern = '/\{Switch\$(.+?)\}([\s\S]+?)\{\/Switch\}/i';
        $c = preg_match_all($pattern, $res, $matches, PREG_SET_ORDER);
        while ($c !== false && $c > 0) {
            foreach ($matches as $block) {
                $blockALL = $block[0];
                $blockKEY = ltrim($block[1], '!');
                $blockHTML = $block[2];
                $blockNOT = (substr($block[1], 0, 1) === '!');

                if (isset($tags[$blockKEY])) {
                    // Loose comparisons with "== true"
                    // http://php.net/manual/en/types.comparisons.php
                    // # $tags[$blockKEY] - $blockNOT - HTML Output
                    // #--------------------------------------------
                    // #  true               true        false
                    // #  true               false       true
                    // #  false              true        true
                    // #  false              false       false
                    // #--------------------------------------------
                    if ($blockNOT xor (bool)$tags[$blockKEY]) {
                        // HTML ON
                        $res = str_replace($blockALL, $blockHTML, $res);
                    } else {
                        // HTML OFF
                        $res = str_replace($blockALL, '', $res);
                    }
                } else {
                    // Remove HTML between switch tag
                    $res = str_replace($blockALL, '', $res);
                }
            }
            $c = preg_match_all($pattern, $res, $matches, PREG_SET_ORDER);
        }
        return $res;
    }

    private function replacer($pageVariables, string $stream): string
    {
        $tags = [];
        $replaces = [];
        foreach ($pageVariables as $key => $value) {
            if (!is_array($value)) {
                $tags[] = '{$$'.$key.'}';
                $replaces[] = $value;
            }
        }
        $res = '';
        // Replace
        $res = str_replace($tags, $replaces, $stream);
        // Delete undefined Tags
        $res = preg_replace('/\{(\$\$.*?)\}/', "", $res);
        // Escape
        return preg_replace('/\{\\\\(.*?)\}/', "{\$\$\\1}", $res);
    }

    private function constSetter(string $stream): string
    {
        $server = new Server($_SERVER);
        $configurator = new Configurator();

        $search = [
            "<head>",
            "<title>",
            "</title>",
            "</head>",
        ];
        $replace = [
            "<head>\n".
            "<!-- ########## ARTICHOKE PAGE-GENERATOR >>>>>>>>>> -->\n".
            '<base href="'.$server->rootURL().'">'."\n".
            '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n".
            '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" crossorigin="anonymous">'."\n".
            '<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js" crossorigin="anonymous"></script>'."\n".
            '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" crossorigin="anonymous"></script>'."\n".
            '<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" crossorigin="anonymous"></script>'."\n".
            '<link rel="stylesheet" href="css/artichoke.css">'."\n".
            '<link rel="icon" type="image/png" href="favicon.png">'."\n".
            "<!-- <<<<<<<<<< ARTICHOKE PAGE-GENERATOR ########## -->",
            "<title>".$configurator->read('title_prefix'),
            $configurator->read('title_suffix')."</title>",
            "</head>",
        ];

        $res = '';
        // add constants
        $res = str_ireplace($search, $replace, $stream);
        // baseURL
        $res = str_replace('{RootURL$}', $server->rootURL(), $res);
        // baseDomain
        return str_replace('{MyFQDN$}', $server->myFQDN(), $res);
    }

    private function ajaxApply(string $stream)
    {
        // search tag
        $a = preg_match_all('/\{AjaxGetText\$(.+)\|(.+)\}/', $stream, $ajaxTag, PREG_SET_ORDER);
        if ($a === false || $a === 0) {
            return $stream;
        }

        $res = $stream;
        // replace
        foreach ($ajaxTag as $founds) {
            $ajaxCode = '<script>$.get("'.$founds[1].'", function(ret){$("#'.$founds[2].'").text(ret);});</script>';
            $res = str_replace($founds[0], $ajaxCode, $res);
        }

        return $res;
    }
}
