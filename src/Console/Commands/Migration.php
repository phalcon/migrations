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

use Exception;
use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils\Config;

use function explode;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_file;
use function json_decode;
use function parse_ini_file;
use function pathinfo;
use function preg_match;
use function realpath;
use function str_repeat;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function trim;
use function yaml_parse_file;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use const PHP_OS;

/**
 * Migration Command
 *
 * Generates/Run a migration
 */
class Migration implements CommandsInterface
{
    final public function __construct(protected Parser $parser)
    {
    }

    /**
     * Print Help information
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

        print Color::head('Usage: Drop the tables, or delete their data (not the log table)') . PHP_EOL;
        print Color::colorize('  migration clean --force [--table=<names>] [--data-only]', Color::FG_GREEN)
            . PHP_EOL . PHP_EOL;

        print Color::head('Usage: Update migration files to Phalcon\Migrations\Db namespace') . PHP_EOL;
        print Color::colorize('  migration migrate-files --migrations=<path>', Color::FG_GREEN) . PHP_EOL . PHP_EOL;

        print Color::head('Arguments:') . PHP_EOL;
        print Color::colorize('  help', Color::FG_GREEN);
        print Color::colorize("\tShows this help text") . PHP_EOL . PHP_EOL;

        $this->printParameters($this->getPossibleParams());
    }

    public function getPossibleParams(): array
    {
        return [
            'config=s'               => 'Configuration file',
            'migrations=s'           => 'Migrations directory. Use comma ' .
                'separated string to specify multiple directories',
            'directory=s'            => 'Directory where the project was created',
            'table=s'                => 'Table to migrate. Table name or table ' .
                'prefix with asterisk. Default: all',
            'version=s'              => 'Version to migrate',
            'descr=s'                => 'Migration description (used for ' .
                'timestamp based migration)',
            'data=s'                 => 'Export data [always|oncreate] (Import ' .
                'data when run migration)',
            'truncate'               => 'Empty each table before its data is ' .
                'imported (Running only)',
            'exportDataFromTables=s' => 'Export data from specific tables, use ' .
                'comma separated string.',
            'force'                  => 'Forces to overwrite existing migrations, ' .
                'or confirms the clean action',
            'data-only'              => 'Delete the data and keep the tables (clean only)',
            'ts-based'               => 'Timestamp based migration version',
            'log-in-db'              => 'Keep migrations log in the database ' .
                'table rather than in file',
            'log-path=s'             => 'Path of the migrations log file ' .
                '(file log only). Default: .phalcon/migration-version',
            'log-table=s'            => 'Name of the migrations log table ' .
                '(database log only). Default: phalcon_migrations',
            'dry'                    => 'Attempt requested operation without ' .
                'making changes to system (Generating only)',
            'verbose'                => 'Output of debugging information ' .
                'during operation (Running only)',
            'no-auto-increment'      => 'Disable auto increment (Generating only)',
            'skip-ref-schema'        => 'Leave out the schema of the referenced ' .
                'table in the foreign keys (Generating only)',
            'skip-foreign-checks'    => 'Turn off the foreign key checks during ' .
                'the migration, MySQL only (Running only)',
            'namespace=s'            => 'Namespace of the generated migration classes (Generating only)',
            'dry-run'                => 'Preview changes without writing (migrate-files only)',
            'help'                   => 'Shows this help [optional]',
        ];
    }

    /**
     * @throws CommandsException
     * @throws Exception
     */
    public function run(): void
    {
        $action = $this->parser->get(0);

        if (in_array($action, [null, 'help', 'h', '?'], true)) {
            $this->getHelp();

            return;
        }

        if ($action === 'migrate-files') {
            (new MigrateFiles($this->parser))->run();

            return;
        }

        $resolved = realpath($this->parser->get('directory', ''));
        $path     = ($resolved !== false ? $resolved : '') . DIRECTORY_SEPARATOR;
        if ($this->parser->has('config')) {
            $configFile = (string) $this->parser->get('config');
            $config     = $this->loadConfig($this->isAbsolutePath($configFile) ? $configFile : $path . $configFile);
        } else {
            $config = $this->getConfig($path);
        }
        // Multiple dir
        $migrationsDir = [];
        if ($this->parser->has('migrations')) {
            $migrationsDir = explode(',', $this->parser->get('migrations'));
        } elseif ($config->migrationsDir !== null) {
            $migrationsDir = explode(',', $config->migrationsDir);
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
        $migrationsInDb = $config->logInDb ?: $this->parser->has('log-in-db');

        /**
         * Migrations naming is timestamp-based rather than traditional, dotted versions
         * either "ts-based" option or "migrationsTsBased" config variable from "application" block
         */
        $migrationsTsBased = $config->migrationsTsBased ?: $this->parser->has('ts-based');

        $noAutoIncrement   = $config->noAutoIncrement   ?: $this->parser->has('no-auto-increment');
        $skipRefSchema     = $config->skipRefSchema     ?: $this->parser->has('skip-ref-schema');
        $skipForeignChecks = $config->skipForeignChecks ?: $this->parser->has('skip-foreign-checks');
        $truncate          = $config->truncate          ?: $this->parser->has('truncate');

        $descr      = $config->descr ?? $this->parser->get('descr');
        $tableName  = $this->parser->get('table', '@');
        $logOptions = $this->logOptions($config, $path);

        switch ($action) {
            case 'generate':
                Migrations::generate([
                    'directory'            => $path,
                    'tableName'            => $tableName,
                    'exportData'           => $this->parser->get('data'),
                    'exportDataFromTables' => $this->exportFromTables($config),
                    'migrationsDir'        => $migrationsDir,
                    'version'              => $this->parser->get('version'),
                    'force'                => $this->parser->has('force'),
                    'tsBased'              => $migrationsTsBased,
                    'noAutoIncrement'      => $noAutoIncrement,
                    'config'               => $config,
                    'descr'                => $descr,
                    'verbose'              => $this->parser->has('dry'),
                    'skip-ref-schema'      => $skipRefSchema,
                    'namespace'            => $this->parser->get('namespace') ?? $config->namespace,
                    ...$logOptions,
                ]);
                break;
            case 'run':
                if ($this->parser->has('dry')) {
                    throw CommandsException::dryOnlyForGenerate();
                }

                Migrations::run([
                    'directory'           => $path,
                    'tableName'           => $tableName,
                    'migrationsDir'       => $migrationsDir,
                    'force'               => $this->parser->has('force'),
                    'tsBased'             => $migrationsTsBased,
                    'config'              => $config,
                    'version'             => $this->parser->get('version'),
                    'migrationsInDb'      => $migrationsInDb,
                    'verbose'             => $this->parser->has('verbose'),
                    'skip-foreign-checks' => $skipForeignChecks,
                    'truncate'            => $truncate,
                    ...$logOptions,
                ]);
                break;
            case 'clean':
                if (!$this->parser->has('force')) {
                    throw CommandsException::cleanNeedsForce();
                }

                Migrations::clean([
                    'config'         => $config,
                    'data-only'      => $this->parser->has('data-only'),
                    'directory'      => $path,
                    'migrationsInDb' => $migrationsInDb,
                    'tableName'      => $tableName,
                    ...$logOptions,
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
                    ...$logOptions,
                ]);
                break;

            default:
                throw CommandsException::unknownAction();
        }
    }

    protected function exportFromTables(Config $config): array
    {
        if ($this->parser->has('exportDataFromTables')) {
            return explode(',', $this->parser->get('exportDataFromTables'));
        }

        return $config->exportDataFromTables;
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

        throw CommandsException::configNotFound();
    }

    /**
     * Check if a path is absolute
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

    /**
     * Determines correct adapter by file name
     * and load config
     *
     * @throws CommandsException
     */
    protected function loadConfig(string $fileName): Config
    {
        if (!is_file($fileName)) {
            throw CommandsException::configNotFound();
        }

        $pathInfo = pathinfo($fileName);

        if (!isset($pathInfo['extension'])) {
            throw CommandsException::configExtensionNotFound();
        }

        $extension = strtolower(trim($pathInfo['extension']));

        switch ($extension) {
            case 'php':
                $data = include($fileName);
                return Config::fromArray(is_array($data) ? $data : []);

            case 'ini':
                return Config::fromArray(parse_ini_file($fileName, true) ?: []);

            case 'json':
                return Config::fromArray(json_decode(file_get_contents($fileName), true) ?? []);

            case 'yaml':
            case 'yml':
                return Config::fromArray(yaml_parse_file($fileName) ?: []);

            default:
                throw CommandsException::configBuilderNotFound();
        }
    }

    /**
     * The options of the migrations log. The CLI options win over the config.
     * A relative log path starts in the project directory.
     */
    protected function logOptions(Config $config, string $path): array
    {
        $logPath = (string) ($this->parser->get('log-path') ?? $config->logPath ?? '');
        if ($logPath !== '' && !$this->isAbsolutePath($logPath)) {
            $logPath = $path . $logPath;
        }

        return [
            'log-path'  => $logPath !== '' ? $logPath : null,
            'log-table' => $this->parser->get('log-table') ?? $config->logTable,
        ];
    }

    /**
     * Prints the available options in the script
     */
    protected function printParameters(array $parameters): void
    {
        $length = 0;
        foreach ($parameters as $parameter => $_) {
            if ($length == 0) {
                $length = strlen($parameter);
            }

            if (strlen($parameter) > $length) {
                $length = strlen($parameter);
            }
        }

        print Color::head('Options:') . PHP_EOL;
        foreach ($parameters as $parameter => $description) {
            print Color::colorize(
                ' --'
                . $parameter
                . str_repeat(' ', $length - strlen($parameter)),
                Color::FG_GREEN
            );
            print Color::colorize('    ' . $description) . PHP_EOL;
        }
    }
}
