<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;

class Cli extends Module
{
    public function _beforeSuite($settings = [])
    {
        /**
         * Cleanup tests output folders
         */
        $this->getModule('\Helper\Integration')->removeDir(codecept_output_dir());
    }
}
