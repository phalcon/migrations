<?php

declare(strict_types=1);

namespace Helper;

use function codecept_output_dir;

class Integration extends \Codeception\Module
{
    /**
     * **HOOK** executed before suite
     *
     * @param array $settings
     */
    public function _beforeSuite($settings = [])
    {
        ob_get_clean();

        /**
         * Cleanup tests output folders
         */
        $this->removeDir(codecept_output_dir());
    }

    /**
     * @param string $path
     * @return void
     */
    public function removeDir(string $path)
    {
        $directoryIterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->getFileName() === '.gitignore') {
                continue;
            }

            $realPath = $file->getRealPath();
            $file->isDir() ? rmdir($realPath) : unlink($realPath);
        }
    }
}
