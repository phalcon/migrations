<?php

/**
 * This file is part of the Phalcon Migrations.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Unit\Console\Commands;

use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Commands\CommandsException;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Tests\Fakes\Console\Commands\MigrationFake;
use Phalcon\Migrations\Utils\Config;

final class MigrationCommandTest extends AbstractTestCase
{
    private function makeParser(array $argv): Parser
    {
        $parser = new Parser();
        $parser->parse(array_merge(['script'], $argv));

        return $parser;
    }

    private function makeCommand(array $argv): MigrationFake
    {
        return new MigrationFake($this->makeParser($argv));
    }

    private function writePhpConfig(string $file): void
    {
        $content  = "<?php\n";
        $content .= "return ['database' => [\n";
        $content .= "    'adapter' => 'mysql', 'host' => 'h',\n";
        $content .= "    'dbname' => 'd', 'username' => 'u', 'password' => '',\n";
        $content .= "]];\n";
        file_put_contents($file, $content);
    }

    public function testGetPossibleParams(): void
    {
        $command = $this->makeCommand([]);

        $params = $command->getPossibleParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('config=s', $params);
        $this->assertArrayHasKey('migrations=s', $params);
        $this->assertArrayHasKey('help', $params);
    }

    public function testRunWithNullActionPrintsHelp(): void
    {
        $command = $this->makeCommand([]);

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
    }

    public function testRunWithHelpActionPrintsHelp(): void
    {
        $command = $this->makeCommand(['help']);

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
    }

    public function testRunWithHActionPrintsHelp(): void
    {
        $command = $this->makeCommand(['h']);

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
    }

    public function testRunWithQuestionMarkPrintsHelp(): void
    {
        $command = $this->makeCommand(['?']);

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
    }

    public function testRunWithUnknownActionThrows(): void
    {
        $dir = $this->getOutputDir('cmd-unknown');
        $this->writePhpConfig($dir . '/config.php');

        $command = $this->makeCommand(['unknown-action', '--directory=' . $dir, '--config=config.php']);

        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage('Unknown action');

        ob_start();
        try {
            $command->run();
        } finally {
            ob_end_clean();
        }
    }

    public function testGetHelp(): void
    {
        $command = $this->makeCommand([]);

        ob_start();
        $command->getHelp();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
        $this->assertStringContainsString('generate', $output);
        $this->assertStringContainsString('run', $output);
        $this->assertStringContainsString('list', $output);
    }

    public function testLoadConfigWithPhpFile(): void
    {
        $dir  = $this->getOutputDir('migration-cmd-config');
        $file = $dir . '/config.php';
        $this->writePhpConfig($file);

        $command = $this->makeCommand([]);
        $config  = $command->publicLoadConfig($file);

        $this->assertSame('mysql', $config->adapter);
        $this->assertSame('h', $config->host);
    }

    public function testLoadConfigWithJsonFile(): void
    {
        $dir  = $this->getOutputDir('migration-cmd-json');
        $file = $dir . '/config.json';
        file_put_contents($file, json_encode([
            'database' => [
                'adapter'  => 'mysql',
                'host'     => 'db-host',
                'dbname'   => 'mydb',
                'username' => 'user',
                'password' => 'pass',
            ],
        ]));

        $command = $this->makeCommand([]);
        $config  = $command->publicLoadConfig($file);

        $this->assertSame('mysql', $config->adapter);
        $this->assertSame('db-host', $config->host);
    }

    public function testLoadConfigWithIniFile(): void
    {
        $dir  = $this->getOutputDir('migration-cmd-ini');
        $file = $dir . '/config.ini';
        file_put_contents(
            $file,
            "[database]\nadapter=mysql\nhost=ini-host\ndbname=inidb\nusername=root\npassword=\n"
        );

        $command = $this->makeCommand([]);
        $config  = $command->publicLoadConfig($file);

        $this->assertSame('mysql', $config->adapter);
        $this->assertSame('ini-host', $config->host);
    }

    public function testLoadConfigThrowsOnUnknownExtension(): void
    {
        $dir  = $this->getOutputDir('migration-cmd-bad-ext');
        $file = $dir . '/config.xml';
        file_put_contents($file, '<config/>');

        $command = $this->makeCommand([]);

        $this->expectException(CommandsException::class);

        $command->publicLoadConfig($file);
    }

    public function testLoadConfigThrowsOnMissingExtension(): void
    {
        $dir  = $this->getOutputDir('migration-cmd-no-ext');
        $file = $dir . '/config';
        file_put_contents($file, 'something');

        $command = $this->makeCommand([]);

        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage('Config file extension not found');

        $command->publicLoadConfig($file);
    }

    public function testGetConfigThrowsWhenNoFileFound(): void
    {
        $dir     = $this->getOutputDir('migration-cmd-no-config');
        $command = $this->makeCommand([]);

        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage("Can't locate the configuration file");

        $command->publicGetConfig($dir . '/');
    }

    public function testGetConfigFindsPhpFile(): void
    {
        $dir     = $this->getOutputDir('migration-cmd-find-config');
        $confDir = $dir . '/config';
        mkdir($confDir, 0755, true);
        $this->writePhpConfig($confDir . '/config.php');

        $command = $this->makeCommand([]);
        $config  = $command->publicGetConfig($dir . '/');

        $this->assertSame('mysql', $config->adapter);
    }

    public function testIsAbsolutePathWithAbsolutePath(): void
    {
        $command = $this->makeCommand([]);

        $this->assertTrue($command->publicIsAbsolutePath('/var/www/html'));
    }

    public function testIsAbsolutePathWithRelativePath(): void
    {
        $command = $this->makeCommand([]);

        $this->assertFalse($command->publicIsAbsolutePath('relative/path'));
        $this->assertFalse($command->publicIsAbsolutePath('migrations'));
    }

    public function testPrintParameters(): void
    {
        $command = $this->makeCommand([]);

        ob_start();
        $command->publicPrintParameters([
            'config=s' => 'Configuration file',
            'force'    => 'Force overwrite',
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Options', $output);
        $this->assertStringContainsString('--config=s', $output);
        $this->assertStringContainsString('--force', $output);
    }

    public function testExportFromTablesFromParser(): void
    {
        $command = $this->makeCommand(['--exportDataFromTables=table1,table2']);
        $config  = Config::fromArray([]);

        $tables = $command->publicExportFromTables($config);

        $this->assertSame(['table1', 'table2'], $tables);
    }

    public function testExportFromTablesFromConfig(): void
    {
        $command = $this->makeCommand([]);
        $config  = Config::fromArray([
            'application' => ['exportDataFromTables' => ['orders', 'products']],
        ]);

        $tables = $command->publicExportFromTables($config);

        $this->assertSame(['orders', 'products'], $tables);
    }
}
