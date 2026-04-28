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
use Phalcon\Migrations\Console\Commands\MigrateFiles;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class MigrateFilesTest extends AbstractTestCase
{
    private function makeParser(array $argv): Parser
    {
        $parser = new Parser();
        $parser->parse(array_merge(['script'], $argv));

        return $parser;
    }

    public function testGetPossibleParams(): void
    {
        $command = new MigrateFiles($this->makeParser([]));

        $params = $command->getPossibleParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('migrations=s', $params);
        $this->assertArrayHasKey('dry-run', $params);
    }

    public function testRunThrowsWhenMigrationsNotProvided(): void
    {
        $command = new MigrateFiles($this->makeParser([]));

        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage('Migrations directory is required');

        $command->run();
    }

    public function testRunThrowsWhenDirectoryNotFound(): void
    {
        $command = new MigrateFiles($this->makeParser(['--migrations=/nonexistent/path/xyz']));

        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage('Directory not found');

        $command->run();
    }

    public function testRunWithDryRunReportsChanges(): void
    {
        $dir = $this->getOutputDir('migrate-files-dry');
        file_put_contents($dir . '/migration.php', "<?php\nuse Phalcon\\Db\\Column;\n");

        $command = new MigrateFiles($this->makeParser(['--migrations=' . $dir, '--dry-run']));

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('dry-run', $output);
        $this->assertStringContainsString('migration.php', $output);
        $this->assertStringContainsString('1 file(s)', $output);
    }

    public function testRunUpdatesMatchingFiles(): void
    {
        $dir  = $this->getOutputDir('migrate-files-update');
        $file = $dir . '/migration.php';
        file_put_contents($file, "<?php\nuse Phalcon\\Db\\Column;\nuse Phalcon\\Db\\Index;\n");

        $command = new MigrateFiles($this->makeParser(['--migrations=' . $dir]));

        ob_start();
        $command->run();
        ob_end_clean();

        $content = file_get_contents($file);
        $this->assertStringContainsString('use Phalcon\\Migrations\\Db\\Column;', $content);
        $this->assertStringContainsString('use Phalcon\\Migrations\\Db\\Index;', $content);
    }

    public function testRunSkipsFilesWithoutMatches(): void
    {
        $dir  = $this->getOutputDir('migrate-files-no-match');
        $file = $dir . '/clean.php';
        file_put_contents($file, "<?php\necho 'hello';\n");

        $command = new MigrateFiles($this->makeParser(['--migrations=' . $dir]));

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('0 file(s)', $output);
    }

    public function testRunSkipsNonPhpFiles(): void
    {
        $dir = $this->getOutputDir('migrate-files-non-php');
        file_put_contents($dir . '/migration.txt', "use Phalcon\\Db\\Column;\n");

        $command = new MigrateFiles($this->makeParser(['--migrations=' . $dir]));

        ob_start();
        $command->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('0 file(s)', $output);
    }

    public function testRunWithPositionalArgument(): void
    {
        $dir = $this->getOutputDir('migrate-files-positional');
        file_put_contents($dir . '/m.php', "<?php\nuse Phalcon\\Db\\Reference;\n");

        $parser = new Parser();
        $parser->parse(['script', 'migrate-files', $dir]);

        $command = new MigrateFiles($parser);

        ob_start();
        $command->run();
        ob_end_clean();

        $content = file_get_contents($dir . '/m.php');
        $this->assertStringContainsString('use Phalcon\\Migrations\\Db\\Reference;', $content);
    }

    public function testGetHelp(): void
    {
        $command = new MigrateFiles($this->makeParser([]));

        ob_start();
        $command->getHelp();
        $output = ob_get_clean();

        $this->assertStringContainsString('Help', $output);
        $this->assertStringContainsString('migrate-files', $output);
        $this->assertStringContainsString('--migrations', $output);
    }
}
