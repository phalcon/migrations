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

namespace Phalcon\Migrations\Tests\Unit\Mvc\Model\Migration\TableAware;

use DirectoryIterator;
use InvalidArgumentException;
use Phalcon\Migrations\Mvc\Model\Migration\TableAware\ListTablesIterator;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class ListTablesIteratorTest extends AbstractTestCase
{
    private ListTablesIterator $iterator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->iterator = new ListTablesIterator();
    }

    public function testListTablesForPrefixThrowsOnEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->iterator->listTablesForPrefix('');
    }

    public function testListTablesForPrefixThrowsOnNullIterator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->iterator->listTablesForPrefix('prefix_', null);
    }

    public function testListTablesForPrefixFiltersFiles(): void
    {
        $dir      = $this->getOutputDir('list-tables-iterator');
        touch($dir . '/prefix_table1.php');
        touch($dir . '/prefix_table2.php');
        touch($dir . '/other_table.php');

        $dirIterator = new DirectoryIterator($dir);
        $result      = $this->iterator->listTablesForPrefix('prefix_', $dirIterator);

        $tables = explode(',', $result);
        $this->assertContains('prefix_table1', $tables);
        $this->assertContains('prefix_table2', $tables);
        $this->assertNotContains('other_table', $tables);
    }

    public function testListTablesForPrefixReturnsEmptyStringWhenNoMatch(): void
    {
        $dir = $this->getOutputDir('list-tables-empty');
        touch($dir . '/other_table.php');

        $dirIterator = new DirectoryIterator($dir);
        $result      = $this->iterator->listTablesForPrefix('prefix_', $dirIterator);

        $this->assertSame('', $result);
    }

    public function testListTablesForPrefixDeduplicatesFiles(): void
    {
        $dir = $this->getOutputDir('list-tables-dedup');
        touch($dir . '/prefix_table1.php');
        touch($dir . '/prefix_table1.dat');

        $dirIterator = new DirectoryIterator($dir);
        $result      = $this->iterator->listTablesForPrefix('prefix_', $dirIterator);

        $tables = explode(',', $result);
        $this->assertSame(['prefix_table1'], array_values(array_unique($tables)));
    }
}
