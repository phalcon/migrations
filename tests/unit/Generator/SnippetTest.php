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

namespace Phalcon\Migrations\Tests\Unit\Generator;

use Phalcon\Migrations\Generator\Snippet;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class SnippetTest extends AbstractTestCase
{
    private Snippet $snippet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->snippet = new Snippet();
    }

    public function testGetMorphTemplateReturnsString(): void
    {
        $template = $this->snippet->getMorphTemplate();

        $this->assertStringContainsString('morphTable', $template);
        $this->assertStringContainsString('%s', $template);
    }

    public function testGetColumnTemplateReturnsString(): void
    {
        $template = $this->snippet->getColumnTemplate();

        $this->assertStringContainsString('new Column', $template);
        $this->assertStringContainsString('%s', $template);
    }

    public function testGetIndexTemplateReturnsString(): void
    {
        $template = $this->snippet->getIndexTemplate();

        $this->assertStringContainsString('new Index', $template);
        $this->assertStringContainsString('%s', $template);
    }

    public function testGetReferenceTemplateReturnsString(): void
    {
        $template = $this->snippet->getReferenceTemplate();

        $this->assertStringContainsString('new Reference', $template);
        $this->assertStringContainsString('%s', $template);
    }

    public function testGetOptionTemplateReturnsString(): void
    {
        $template = $this->snippet->getOptionTemplate();

        $this->assertSame('%s', $template);
    }

    public function testDefinitionToStringWithItems(): void
    {
        $result = $this->snippet->definitionToString('columns', ['item1', 'item2']);

        $this->assertStringContainsString("'columns'", $result);
        $this->assertStringContainsString('item1', $result);
        $this->assertStringContainsString('item2', $result);
    }

    public function testDefinitionToStringWithEmptyItemsReturnsEmptyString(): void
    {
        $result = $this->snippet->definitionToString('columns', []);

        $this->assertSame('', $result);
    }
}
