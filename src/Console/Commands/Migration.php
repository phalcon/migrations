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
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Mvc\Model\Exception;

/**
 * Migration Command
 *
 * Generates/Run a migration
 */
class Migration extends Command
{
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
     * @throws ScriptException
     * @throws Exception
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
                if (!$this->path->isAbsolutePath($dir)) {
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

        $descr = $config['application']['descr'] ?? $this->parser->get('descr');
        $tableName = $this->parser->get('table', '@');

        switch ($action) {
            case 'generate':
                Migrations::generate([
                    'directory'       => $path,
                    'tableName'       => $tableName,
                    'exportData'      => $this->parser->get('data'),
                    'exportDataFromTables'      => $this->exportFromTables($config),
                    'migrationsDir'   => $migrationsDir,
                    'version'         => $this->parser->get('version'),
                    'force'           => $this->parser->has('force'),
                    'noAutoIncrement' => $this->parser->has('no-auto-increment'),
                    'config'          => $config,
                    'descr'           => $descr,
                    'verbose'         => $this->parser->has('dry'),
                ]);
                break;
            case 'run':
                Migrations::run([
                    'directory'      => $path,
                    'tableName'      => $tableName,
                    'migrationsDir'  => $migrationsDir,
                    'force'          => $this->parser->has('force'),
                    'tsBased'        => $migrationsTsBased,
                    'config'         => $config,
                    'version'        => $this->parser->get('version'),
                    'migrationsInDb' => $migrationsInDb,
                    'verbose'        => $this->parser->has('verbose'),
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function getCommands(): array
    {
        return ['migration', 'create-migration'];
    }

    /**
     * {@inheritdoc}
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
}
