<?php

/**
 * This file is part of the Phalcon Developer Tools.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Migrations\Console\Commands;

use Phalcon\Config;
use Phalcon\Config\Adapter\Ini as IniConfig;
use Phalcon\Config\Adapter\Json as JsonConfig;
use Phalcon\Config\Adapter\Yaml as YamlConfig;
use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Migrations;

/**
 * Migration Command
 *
 * Generates/Run a migration
 */
class Migration implements CommandsInterface
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param Parser $parser
     */
    final public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return array
     */
    public function getPossibleParams(): array
    {
        return [
            'config=s' => 'Configuration file',
            'migrations=s' => 'Migrations directory. Use comma separated string to specify multiple directories',
            'directory=s' => 'Directory where the project was created',
            'table=s' => 'Table to migrate. Table name or table prefix with asterisk. Default: all',
            'version=s' => 'Version to migrate',
            'descr=s' => 'Migration description (used for timestamp based migration)',
            'data=s' => 'Export data [always|oncreate] (Import data when run migration)',
            'exportDataFromTables=s' => 'Export data from specific tables, use comma separated string.',
            'force' => 'Forces to overwrite existing migrations',
            'ts-based' => 'Timestamp based migration version',
            'log-in-db' => 'Keep migrations log in the database table rather than in file',
            'dry' => 'Attempt requested operation without making changes to system (Generating only)',
            'verbose' => 'Output of debugging information during operation (Running only)',
            'no-auto-increment' => 'Disable auto increment (Generating only)',
            'help' => 'Shows this help [optional]',
        ];
    }

    /**
     * @throws CommandsException
     * @throws \Phalcon\Db\Exception
     * @throws \Exception
     */
    public function run(): void
    {
        $action = $this->parser->get(0);

        if (in_array($action, [null, 'help', 'h', '?'], true)) {
            $this->getHelp();

            return;
        }

        $path = realpath($this->parser->get('directory', '')) . DIRECTORY_SEPARATOR;
        if ($this->parser->has('config')) {
            $config = $this->loadConfig($path . $this->parser->get('config'));
        } else {
            $config = $this->getConfig($path);
        }

        // Multiple dir
        $migrationsDir = [];
        if ($this->parser->has('migrations')) {
            $migrationsDir = explode(',', $this->parser->get('migrations'));
        } elseif (isset($config['application']['migrationsDir'])) {
            $migrationsDir = explode(',', $config['application']['migrationsDir']);
        }

        if (!empty($migrationsDir)) {
            foreach ($migrationsDir as $id => $dir) {
                if (!$this->isAbsolutePath($dir)) {
                    $migrationsDir[$id] = $path . $dir;
                }
            }
        } elseif (file_exists($path . 'app')) {
            $migrationsDir[] = $path . 'app/migrations';
        } elseif (file_exists($path . 'apps')) {
            $migrationsDir[] = $path . 'apps/migrations';
        } else {
            $migrationsDir[] = $path . 'migrations';
        }

        /**
         * Keep migrations log in db either "log-in-db" option or "logInDb"
         * config variable from "application" block
         */
        $migrationsInDb = $config['application']['logInDb'] ?? $this->parser->has('log-in-db');

        /**
         * Migrations naming is timestamp-based rather than traditional, dotted versions
         * either "ts-based" option or "migrationsTsBased" config variable from "application" block
         */
        $migrationsTsBased = $config['application']['migrationsTsBased'] ?? $this->parser->has('ts-based');

        $noAutoIncrement = $config['application']['no-auto-increment'] ?? $this->parser->has('no-auto-increment');
        $skipRefSchema = $config['application']['skip-ref-schema'] ?? $this->parser->has('skip-ref-schema');
        $skipForeignChecks = $config['application']['skip-foreign-checks'] ?? $this->parser->has('skip-foreign-checks');

        $descr = $config['application']['descr'] ?? $this->parser->get('descr');
        $tableName = $this->parser->get('table', '@');

        switch ($action) {
            case 'generate':
                Migrations::generate([
                    'directory'             => $path,
                    'tableName'             => $tableName,
                    'exportData'            => $this->parser->get('data'),
                    'exportDataFromTables'  => $this->exportFromTables($config),
                    'migrationsDir'         => $migrationsDir,
                    'version'               => $this->parser->get('version'),
                    'force'                 => $this->parser->has('force'),
                    'noAutoIncrement'       => $noAutoIncrement,
                    'config'                => $config,
                    'descr'                 => $descr,
                    'verbose'               => $this->parser->has('dry'),
                    'skip-ref-schema'       => $skipRefSchema,
                ]);
                break;
            case 'run':
                Migrations::run([
                    'directory'             => $path,
                    'tableName'             => $tableName,
                    'migrationsDir'         => $migrationsDir,
                    'force'                 => $this->parser->has('force'),
                    'tsBased'               => $migrationsTsBased,
                    'config'                => $config,
                    'version'               => $this->parser->get('version'),
                    'migrationsInDb'        => $migrationsInDb,
                    'verbose'               => $this->parser->has('verbose'),
                    'skip-foreign-checks'   => $skipForeignChecks,
                ]);
                break;
            case 'list':
                Migrations::listAll([
                    'directory'      => $path,
                    'tableName'      => $tableName,
                    'migrationsDir'  => $migrationsDir,
                    'force'          => $this->parser->has('force'),
                    'tsBased'        => $migrationsTsBased,
                    'config'         => $config,
                    'version'        => $this->parser->get('version'),
                    'migrationsInDb' => $migrationsInDb,
                ]);
                break;

            default:
                throw new CommandsException('Unknown action. Use help, h or ? to see all available commands');
        }
    }

    /**
     * Print Help information
     *
     * @return void
     */
    public function getHelp(): void
    {
        print Color::head('Help:') . PHP_EOL;
        print Color::colorize('  Generates/Run a Migration') . PHP_EOL . PHP_EOL;

        print Color::head('Usage: Generate a Migration') . PHP_EOL;
        print Color::colorize('  migration generate', Color::FG_GREEN) . PHP_EOL . PHP_EOL;

        print Color::head('Usage: Run a Migration') . PHP_EOL;
        print Color::colorize('  migration run', Color::FG_GREEN) . PHP_EOL . PHP_EOL;

        print Color::head('Usage: List all available migrations') . PHP_EOL;
        print Color::colorize('  migration list', Color::FG_GREEN) . PHP_EOL . PHP_EOL;

        print Color::head('Arguments:') . PHP_EOL;
        print Color::colorize('  help', Color::FG_GREEN);
        print Color::colorize("\tShows this help text") . PHP_EOL . PHP_EOL;

        $this->printParameters($this->getPossibleParams());
    }

    /**
     * @param mixed $config
     * @return array
     */
    protected function exportFromTables($config): array
    {
        $tables = [];

        if ($this->parser->has('exportDataFromTables')) {
            $tables = explode(',', $this->parser->get('exportDataFromTables'));
        } elseif (isset($config['application']['exportDataFromTables'])) {
            $configTables = $config['application']['exportDataFromTables'];
            if ($configTables instanceof Config) {
                $tables = $configTables->toArray();
            } else {
                $tables = explode(',', $configTables);
            }
        }

        return $tables;
    }

    /**
     * @param string $path Config path
     *
     * @return Config
     * @throws CommandsException
     */
    protected function getConfig(string $path): Config
    {
        foreach (['app/config/', 'config/'] as $configPath) {
            foreach (['ini', 'php', 'json', 'yaml', 'yml'] as $extension) {
                $configFilePath = $path . $configPath . 'config.' . $extension;
                if (file_exists($configFilePath)) {
                    return $this->loadConfig($configFilePath);
                }
            }
        }

        /**
         * TODO
         * Re-think current approach
         * as it scans whole project like that
         * Which is unacceptable
         */
        /*$directory = new \RecursiveDirectoryIterator('.');
        $iterator = new \RecursiveIteratorIterator($directory);
        foreach ($iterator as $f) {
            if (preg_match('/config\.(php|ini|json|yaml|yml)$/i', $f->getPathName())) {
                return $this->loadConfig($f->getPathName());
            }
        }*/

        throw new CommandsException("Can't locate the configuration file.");
    }

    /**
     * Determines correct adapter by file name
     * and load config
     *
     * @param string $fileName Config file name
     *
     * @return Config
     * @throws CommandsException
     */
    protected function loadConfig(string $fileName): Config
    {
        $pathInfo = pathinfo($fileName);

        if (!isset($pathInfo['extension'])) {
            throw new CommandsException('Config file extension not found.');
        }

        $extension = strtolower(trim($pathInfo['extension']));

        switch ($extension) {
            case 'php':
                $config = include($fileName);
                if (is_array($config)) {
                    $config = new Config($config);
                }

                return $config;

            case 'ini':
                return new IniConfig($fileName);

            case 'json':
                return new JsonConfig($fileName);

            case 'yaml':
            case 'yml':
                return new YamlConfig($fileName);

            default:
                throw new CommandsException("Builder can't locate the configuration file.");
        }
    }

    /**
     * Prints the available options in the script
     *
     * @param array $parameters
     */
    protected function printParameters(array $parameters): void
    {
        $length = 0;
        foreach ($parameters as $parameter => $description) {
            if ($length == 0) {
                $length = strlen($parameter);
            }

            if (strlen($parameter) > $length) {
                $length = strlen($parameter);
            }
        }

        print Color::head('Options:') . PHP_EOL;
        foreach ($parameters as $parameter => $description) {
            print Color::colorize(' --' . $parameter . str_repeat(' ', $length - strlen($parameter)), Color::FG_GREEN);
            print Color::colorize('    ' . $description) . PHP_EOL;
        }
    }

    /**
     * Check if a path is absolute
     *
     * @param string $path Path to check
     * @return bool
     */
    protected function isAbsolutePath(string $path): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (preg_match('/^[A-Z]:\\\\/', $path)) {
                return true;
            }
        } else {
            if (substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
                return true;
            }
        }

        return false;
    }
}
