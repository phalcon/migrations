<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;

class Cli extends Module
{
    public function _before(TestInterface $test)
    {
        /**
         * Cleanup tests output folders
         */
        $this->getModule('\Helper\Integration')->removeDir(codecept_output_dir());
    }
}
