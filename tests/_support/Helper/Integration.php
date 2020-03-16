<?php

declare(strict_types=1);

namespace Helper;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;

use function \codecept_output_dir;

class Integration extends \Codeception\Module
{
    /**
     * @var array
     */
    protected static $generateConfig;

    /**
     * @var AbstractPdo
     */
    protected $db;

    public function _initialize()
    {
        self::$generateConfig = new Config([
            'database' => [
                'adapter' => getenv('MYSQL_TEST_DB_ADAPTER'),
                'host' => getenv('MYSQL_TEST_DB_HOST'),
                'port' => getenv('MYSQL_TEST_DB_PORT'),
                'username' => getenv('MYSQL_TEST_DB_USER'),
                'password' => getenv('MYSQL_TEST_DB_PASSWORD'),
                'dbname' => getenv('MYSQL_TEST_DB_DATABASE'),
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);

        ob_start();
    }

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

    /**
     * @see https://gist.github.com/afischoff/9608738
     * @see https://github.com/phalcon/cphalcon/issues/14620
     *
     * @param AbstractPdo $db
     * @param string $table
     * @param array $columns
     * @param array $rows
     */
    function db_batch_insert(AbstractPdo $db, string $table, array $columns, array $rows): void
    {
        $str = '';
        foreach ($rows as $values) {
            foreach ($values as &$val) {
                if (is_null($val)) {
                    $val = 'NULL';
                    continue;
                }

                if (is_string($val)) {
                    $val = $db->escapeString($val);
                }
            }

            $str .= sprintf('(%s),', implode(',', $values));
        }

        $str = rtrim($str, ',');
        $str .= ';';
        $query = sprintf(
            "INSERT INTO `%s` (%s) VALUES %s",
            $table,
            sprintf('`%s`', implode('`,`', $columns)),
            $str
        );

        $db->execute($query);
    }
}
