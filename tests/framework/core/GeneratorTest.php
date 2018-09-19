<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

use PHPUnit\Framework\TestCase;
use artichoke\framework\core\Generator;
use artichoke\framework\core\Configurator;
use artichoke\framework\core\Server;
use artichoke\framework\util\GetPaths;

class GeneratorTest extends TestCase
{
    private $root;

    const TEST_TMP_FILE_NAME = 'testfile.txt';

    const TEST_ARRAY_KEY1 = 'key1';
    const TEST_ARRAY_KEY2 = 'key2';

    /**
     * @doesNotPerformAssertions
     */
    public function gen_root_dir()
    {
        $divided_dir = explode('/', __DIR__);

        $this->root = '';
        foreach ($divided_dir as $val) {
            if ($val !== '') {
                if ($val === 'tests') {
                    break;
                }
                $this->root = $this->root.'/'.$val;
            }
        }
    }

    public function setUp()
    {
        //Initialize configurator
        $this->gen_root_dir();
        $force_path = array('framework' => $this->root.'/tests/testparam2/Seed.ini');
        $config = (new Configurator())->initialize($this->root, 'artichoke', $force_path);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_run($testClass)
    {
        $setter = function ($tags = [], $fTags = [], $option = null) {
            return $this->run($tags, $fTags, $option);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_binder($testClass)
    {
        $setter = function (string $view_root, string $fileStream) {
            return $this->binder($view_root, $fileStream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setViewRoot($testClass)
    {
        $setter = function ($root_dir, $app_dir, GetPaths $get_paths) {
            return $this->setViewRoot($root_dir, $app_dir, $get_paths);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setPagePath($testClass)
    {
        $setter = function ($page, string $root_dir, GetPaths $get_paths) {
            return $this->setPagePath($page, $root_dir, $get_paths);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_foreacher($testClass)
    {
        $setter = function ($pageArrays, string $stream) {
            return $this->foreacher($pageArrays, $stream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setIncludeTagData($testClass)
    {
        $setter = function (string $file_type, string $file_name) {
            return $this->setIncludeTagData($file_type, $file_name);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_lookUpRenderStreamTagPair($testClass)
    {
        $setter = function (string $renderStream) {
            return $this->lookUpRenderStreamTagPair($renderStream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_setReplaceStrings($testClass)
    {
        $setter = function (array $pageArrays, string $matchstr, array $hitvar) {
            return $this->setReplaceStrings($pageArrays, $matchstr, $hitvar);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_switcher($testClass)
    {
        $setter = function ($tags, string $stream) {
            return $this->switcher($tags, $stream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_replacer($testClass)
    {
        $setter = function ($pageVariables, string $stream) {
            return $this->replacer($pageVariables, $stream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_constSetter($testClass)
    {
        $setter = function (string $stream) {
            return $this->constSetter($stream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    /**
     * @doesNotPerformAssertions
     */
    public function bound_ajaxApply($testClass)
    {
        $setter = function (string $stream) {
            return $this->ajaxApply($stream);
        };
        $bound = $setter->bindTo($testClass, $testClass);

        return $bound;
    }

    //Test for setViewRoot
    public function test_setViewRoot_NormalCase()
    {
        $generator = new Generator();
        $this->bound_setViewRoot($generator)($this->root, 'app', new GetPaths());

        $res = $generator->getAllParams();

        $this->assertEquals($res['viewRoot'], $this->root.'/artichoke/app/views');
    }

    public function test_setViewRoot_Null()
    {
        $generator = new Generator();
        $this->bound_setViewRoot($generator)($this->root, null, new GetPaths());

        $res = $generator->getAllParams();

        $this->assertEquals($res['viewRoot'], $this->root.'/artichoke/framework/views');
    }

    //Test for setPagePath
    public function test_setPagePath_TemplateReadable()
    {
        $generator = new Generator();
        $this->bound_setViewRoot($generator)($this->root, 'framework', new GetPaths());

        $this->bound_setPagePath($generator)('index', $this->root, new GetPaths());

        $res = $generator->getAllParams();

        $this->assertEquals($res['pagePath'], $this->root.'/artichoke/framework/views/template/index.html');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_setPagePath_TemplateNotReadable()
    {
        $generator = new Generator();
        $this->bound_setViewRoot($generator)($this->root, 'app', new GetPaths());

        $_SERVER['SERVER_PROTOCOL'] = 'protocol';
        $_SERVER['SERVER_NAME'] = 'name';
        $this->bound_setPagePath($generator)('index', $this->root, new GetPaths());

        $res = $generator->getAllParams();

        $this->assertEquals($res['pagePath'], $this->root.'/artichoke/framework/views/template/exception.html');
    }

    //Test for setIncludeTagData
    /**
     * @doesNotPerformAssertions
     */
    public function gen_expectTagData(string $head, string $notfound, string $tail): array
    {
        return array(
            'Head' => $head,
            'notfound' => $notfound,
            'Tail' => $tail,
        );
    }

    public function test_setIncludeTagData_FileTypeJS()
    {
        $generator = new Generator();
        $out = $this->bound_setIncludeTagData($generator)('js', self::TEST_TMP_FILE_NAME);

        $expect_out = $this->gen_expectTagData(
            "<script type=\"text/javascript\">\n",
            "<!-- Javascript \"".self::TEST_TMP_FILE_NAME."\" not found -->\n",
            "\n</script>\n"
        );

        $this->assertEquals($out, $expect_out);
    }

    public function test_setIncludeTagData_FileTypeCSS()
    {
        $generator = new Generator();
        $out = $this->bound_setIncludeTagData($generator)('css', '');

        $expect_out = $this->gen_expectTagData(
            "<style type=\"text/css\">\n",
            "<!-- CSS \"\" not found -->\n",
            "\n</style>\n"
        );

        $this->assertEquals($out, $expect_out);
    }

    public function test_setIncludeTagData_FileTypeHTM()
    {
        $generator = new Generator();
        $out = $this->bound_setIncludeTagData($generator)('htm', self::TEST_TMP_FILE_NAME);

        $expect_out = $this->gen_expectTagData(
            '',
            "<!-- HTML \"".self::TEST_TMP_FILE_NAME."\" not found -->\n",
            ''
        );

        $this->assertEquals($out, $expect_out);
    }

    public function test_setIncludeTagData_FileTypeHTML()
    {
        $generator = new Generator();
        $out = $this->bound_setIncludeTagData($generator)('html', self::TEST_TMP_FILE_NAME);

        $expect_out = $this->gen_expectTagData(
            '',
            "<!-- HTML \"".self::TEST_TMP_FILE_NAME."\" not found -->\n",
            ''
        );

        $this->assertEquals($out, $expect_out);
    }

    public function test_setIncludeTagData_FileTypeNotMatch()
    {
        $generator = new Generator();
        $out = $this->bound_setIncludeTagData($generator)('', self::TEST_TMP_FILE_NAME);

        $expect_out = $this->gen_expectTagData(
            '',
            "<!-- Resource \"".self::TEST_TMP_FILE_NAME."\" not found -->\n",
            ''
        );

        $this->assertEquals($out, $expect_out);
    }

    //Test for foreacher
    public function test_foreachre_UseCase()
    {
        $testStream = '{Foreach} {F$'.self::TEST_ARRAY_KEY1.'}{/Foreach}{Foreach} {F$'.self::TEST_ARRAY_KEY2.'}{/Foreach}{Foreach} {F$piyo}{/Foreach}';
        $pageArrays = array(
            self::TEST_ARRAY_KEY1 => ['val1'],
        );

        $generator = new Generator();
        $out = $this->bound_foreacher($generator)($pageArrays, $testStream);

        $this->assertEquals($out, ' val1  ');
    }

    public function test_foreachre_PregNotMatch()
    {
        $testStream = '{Foreach} '.self::TEST_ARRAY_KEY1.'{/Foreach}{Foreach} hoge{/Foreach}{Foreach} {F$piyo}{/Foreach}';
        $pageArrays = array(
            self::TEST_ARRAY_KEY1 => ['val1'],
        );

        $generator = new Generator();
        $out = $this->bound_foreacher($generator)($pageArrays, $testStream);

        $this->assertEquals($out, '   ');
    }

    //Test for lookUpRenderStreamTagPair
    public function test_lookUpRenderStreamTagPair_UseCase()
    {
        $test_render_param = '{Foreach} test{/Foreach}{Foreach} hoge{/Foreach}{Foreach} piyo{/Foreach}';
        $generator = new Generator();
        $out = $this->bound_lookUpRenderStreamTagPair($generator)($test_render_param);

        $expected_out = array('test', 'hoge', 'piyo');

        $this->assertEquals($out, $expected_out);
    }

    public function test_lookUpRenderStreamTagPair_CheckLength()
    {
        $test_render_param = '{Foreach}test{/Foreach}{Foreach} hoge{/Foreach}{Foreach}piyo{/Foreach}';
        $generator = new Generator();
        $out = $this->bound_lookUpRenderStreamTagPair($generator)($test_render_param);

        $expected_out = array('est', 'hoge', 'iyo');

        $this->assertEquals($out, $expected_out);
    }

    public function test_lookUpRenderStreamTagPair_NotMatch()
    {
        $test_render_param = '';
        $generator = new Generator();
        $out = $this->bound_lookUpRenderStreamTagPair($generator)($test_render_param);

        $this->assertEquals($out, []);
    }

    //Test for setReplaceStrings
    public function test_setReplaceStrings_ReplacePattern1()
    {
        $hitvar = array(
            '012'.self::TEST_ARRAY_KEY1.'E',
            '012'.self::TEST_ARRAY_KEY2.'E',
        );

        $pageArrays = array(
            self::TEST_ARRAY_KEY1 => ['val1', 'replaced1'],
            'other' => 'test',
            self::TEST_ARRAY_KEY2 => ['val2', 'replaced2'],
        );

        //$matcher = '012key1E_test';
        $matcher = $hitvar[0].'_test';

        $generator = new Generator();
        $out = $this->bound_setReplaceStrings($generator)($pageArrays, $matcher, $hitvar);

        $this->assertEquals($out, 'val1_testreplaced1_test');
    }

    public function test_setReplaceStrings_ReplacePattern2()
    {
        $hitvar = array(
            '012'.self::TEST_ARRAY_KEY2.'E',
            '012'.self::TEST_ARRAY_KEY1.'E',
        );

        $pageArrays = array(
            self::TEST_ARRAY_KEY1 => ['val1', 'replaced1'],
            'other' => 'test',
            self::TEST_ARRAY_KEY2 => ['val2'],
        );

        $matcher = $hitvar[0].'_test';

        $generator = new Generator();
        $out = $this->bound_setReplaceStrings($generator)($pageArrays, $matcher, $hitvar);

        $this->assertEquals($out, 'val2_test');
    }

    public function test_setReplaceStrings_Nomatch()
    {
        $hitvar = array(
            self::TEST_ARRAY_KEY2,
            self::TEST_ARRAY_KEY1,
        );

        $pageArrays = array(
            self::TEST_ARRAY_KEY1 => ['val1', 'replaced1'],
            'other' => 'test',
            self::TEST_ARRAY_KEY2 => ['val2'],
        );

        $matcher = $hitvar[0].'_test';

        $generator = new Generator();
        $out = $this->bound_setReplaceStrings($generator)($pageArrays, $matcher, $hitvar);

        $this->assertEquals($out, '');
    }

    //Test for switcher
    public function test_switcher_UseCase()
    {
        $tags = array('failedMes' => 'Error');
        $testStream = '{Switch$failedMes}<div class="alert alert-warning" role="alert">{$$failedMes}</div>{/Switch}';

        $generator = new Generator();
        $out = $this->bound_switcher($generator)($tags, $testStream);

        $this->assertEquals($out, '<div class="alert alert-warning" role="alert">{$$failedMes}</div>');
    }

    public function test_switcher_PregNotMatch()
    {
        $tags = array('failedMes' => 'Error');
        $testStream = '{aSwitch$failedMes}<div class="alert alert-warning" role="alert">{$$failedMes}</div>{/Switch}';

        $generator = new Generator();
        $out = $this->bound_switcher($generator)($tags, $testStream);

        $this->assertEquals($out, $testStream);
    }

    public function test_switcher_NoHtmlTag()
    {
        $tags = array('failedMes' => 'Error');
        $testStream = '{Switch$failedMes}test{/Switch}';

        $generator = new Generator();
        $out = $this->bound_switcher($generator)($tags, $testStream);

        $this->assertEquals($out, 'test');
    }

    //Test for replacer
    public function test_replacer_NormalCase()
    {
        $pageVariables = array(
            'key1' => 'replace1',
            'key2' => 'replace2',
        );

        $testStream = '{$$key1}test{$$key2}test';

        $generator = new Generator();
        $out = $this->bound_replacer($generator)($pageVariables, $testStream);

        $this->assertEquals($out, 'replace1testreplace2test');
    }

    public function test_replacer_NotReplace()
    {
        $pageVariables = array(
            'key1' => 'replace1',
            'key2' => 'replace2',
        );

        $testStream = '{$$key3}test{$$key1}test';

        $generator = new Generator();
        $out = $this->bound_replacer($generator)($pageVariables, $testStream);

        $this->assertEquals($out, 'testreplace1test');
    }

    public function test_replacer_CheckPreg2()
    {
        $pageVariables = array(
            'key1' => 'replace1',
            'key2' => 'replace2',
        );

        $testStream = '{$$key3}test{$$key1}test{\key1}';

        $generator = new Generator();
        $out = $this->bound_replacer($generator)($pageVariables, $testStream);

        $this->assertEquals($out, 'testreplace1test{$$key1}');
    }

    //Test for constSetter
    /**
     * @doesNotPerformAssertions
     */
    public function initializeServer()
    {
        $_SERVER['SERVER_NAME'] = 'server';
    }

    public function getConstSetterReplace(): string
    {
        $server = new Server($_SERVER);

        return
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
            "<!-- <<<<<<<<<< ARTICHOKE PAGE-GENERATOR ########## -->";
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_CheckHead()
    {
        $this->initializeServer();

        $testStream = '<head>header';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $expected_out = $this->getConstSetterReplace();

        $this->assertEquals($out, $expected_out.'header');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_CheckTitle()
    {
        $this->initializeServer();

        $testStream = '<title>title';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $expected_out = (new Configurator())->read('title_prefix');

        $this->assertEquals($out, '<title>'.$expected_out.'title');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_CheckSlashTitle()
    {
        $this->initializeServer();

        $testStream = 'title</title>';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $expected_out = (new Configurator())->read('title_suffix');

        $this->assertEquals($out, 'title'.$expected_out.'</title>');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_CheckSlashHead()
    {
        $this->initializeServer();

        $testStream = 'head</head>';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $this->assertEquals($out, $testStream);
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_RootURL()
    {
        $this->initializeServer();

        $testStream = '{RootURL$} : url';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $rootURL = (new Server($_SERVER))->rootURL();
        $this->assertEquals($out, $rootURL.' : url');
    }

    /**
     * @runInSeparateProcess
     */
    public function test_constSetter_MyFQDN()
    {
        $this->initializeServer();

        $testStream = '{MyFQDN$} : fqdn';

        $generator = new Generator();
        $out = $this->bound_constSetter($generator)($testStream);

        $fqdn = (new Server($_SERVER))->myFQDN();
        $this->assertEquals($out, $fqdn.' : fqdn');
    }

    //Test for ajaxApply
    public function test_ajaxApply_NormalCase()
    {
        $this->initializeServer();

        $testStream = '{AjaxGetText$test_url|test_id}';

        $generator = new Generator();
        $out = $this->bound_ajaxApply($generator)($testStream);

        $this->assertEquals($out, '<script>$.get("'.'test_url'.'", function(ret){$("#'.'test_id'.'").text(ret);});</script>');
    }

    public function test_ajaxApply_NotMatch()
    {
        $this->initializeServer();

        $testStream = '{AjaxGetText$test_url}';

        $generator = new Generator();
        $out = $this->bound_ajaxApply($generator)($testStream);

        $this->assertEquals($out, $testStream);
    }

    // Test for run
    /**
     * @runInSeparateProcess
     */
    public function test_run_CaseTemplateOnly()
    {
        $generator = new Generator();
        $out = $this->bound_run($generator)([], [], 'template_only');

        $this->assertEquals($out, $generator->getAllParams()['fileStream']);
    }

    public function test_run_CaseTemplateBinded()
    {
        $generator = new Generator();
        $out = $this->bound_run($generator)([], [], 'template_binded');

        $generator_ref = new Generator();
        $viewRoot = $generator->getAllParams()['viewRoot'];
        $fileStream = file_get_contents($generator->getAllParams()['pagePath']);
        $render_stream = $this->bound_binder($generator_ref)($viewRoot, $fileStream);
        $render_stream = $this->bound_constSetter($generator_ref)($render_stream);

        $this->assertEquals($out, $render_stream);
    }
}
