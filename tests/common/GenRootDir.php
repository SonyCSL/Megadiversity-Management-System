<?php

/* Copyright 2018 Sony Computer Science Laboratories, Inc. */

class GenRootDir
{
    public function gen_root_dir(): string
    {
        $res = '';
        $divided_dir = explode('/', __DIR__);

        foreach ($divided_dir as $val) {
            if ($val !== '') {
                if ($val === 'tests') {
                    break;
                }
                $res = $res.'/'.$val;
            }
        }

        return $res;
    }
}
