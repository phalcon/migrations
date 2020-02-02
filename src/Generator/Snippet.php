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

namespace Phalcon\Migrations\Generator;

use Phalcon\Db\ColumnInterface;
use Phalcon\Migrations\Options\OptionsAware as ModelOption;

class Snippet
{
    /**
     * @param string $namespace
     * @param string $useDefinition
     * @param string $classDoc
     * @param string $abstract
     * @param ModelOption|null $modelOptions
     * @param string $extends
     * @param string $content
     * @param string $license
     * @return string
     */
    public function getClass(
        $namespace,
        $useDefinition,
        $classDoc = '',
        $abstract = '',
        ModelOption $modelOptions = null,
        $extends = '',
        $content = '',
        $license = ''
    ): string {
        $templateCode = <<<EOD
<?php

%s%s%s%s%sclass %s extends %s
{
%s
}
EOD;
        return sprintf(
            $templateCode,
            $license,
            $namespace,
            $useDefinition,
            $classDoc,
            $abstract,
            $modelOptions->getOption('className'),
            $extends,
            $content
        ) . PHP_EOL;
    }

    public function getAttributes(
        $type,
        $visibility,
        ColumnInterface $field,
        $annotate = false,
        $customFieldName = null
    ): string {
        $fieldName = $customFieldName ?: $field->getName();

        if ($annotate) {
            $templateAttributes = <<<EOD
    /**
     *
     * @var %s%s%s
     * @Column(column="%s", type="%s"%s, nullable=%s)
     */
    %s \$%s;
EOD;

            return PHP_EOL . sprintf(
                $templateAttributes,
                $type,
                $field->isPrimary() ? PHP_EOL . '     * @Primary' : '',
                $field->isAutoIncrement() ? PHP_EOL . '     * @Identity' : '',
                $field->getName(),
                $type,
                $field->getSize() ? ', length=' . $field->getSize() : '',
                $field->isNotNull() ? 'false' : 'true',
                $visibility,
                $fieldName
            ) . PHP_EOL;
        } else {
            $templateAttributes = <<<EOD
    /**
     *
     * @var %s
     */
    %s \$%s;
EOD;

            return PHP_EOL . sprintf($templateAttributes, $type, $visibility, $fieldName) . PHP_EOL;
        }
    }

    public function getMigrationMorph($className, $table, $tableDefinition): string
    {
        $template = <<<EOD
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Mvc\Model\Migration;

/**
 * Class %s
 */
class %s extends Migration
{
    /**
     * Define the table structure
     *
     * @return void
     */
    public function morph()
    {
        \$this->morphTable('%s', [
%s
EOD;
        return sprintf(
            $template,
            $className,
            $className,
            $table,
            $this->getMigrationDefinition('columns', $tableDefinition)
        );
    }

    public function getMigrationUp(): string
    {
        return <<<EOD

    /**
     * Run the migrations
     *
     * @return void
     */
    public function up()
    {

EOD;
    }

    public function getMigrationDown(): string
    {
        return <<<EOD

    /**
     * Reverse the migrations
     *
     * @return void
     */
    public function down()
    {

EOD;
    }

    public function getMigrationBatchInsert($table, $allFields): string
    {
        $template = <<<EOD
        \$this->batchInsert('%s', [
                %s
            ]
        );
EOD;
        return sprintf($template, $table, join(",\n                ", $allFields));
    }

    public function getMigrationAfterCreateTable($table, $allFields): string
    {
        $template = <<<EOD

    /**
     * This method is called after the table was created
     *
     * @return void
     */
     public function afterCreateTable()
     {
        \$this->batchInsert('%s', [
                %s
            ]
        );
     }
EOD;
        return sprintf($template, $table, join(",\n                ", $allFields));
    }

    public function getMigrationBatchDelete($table): string
    {
        $template = <<<EOD
        \$this->batchDelete('%s');
EOD;
        return sprintf($template, $table);
    }

    public function getMigrationDefinition($name, $definition): string
    {
        $template = <<<EOD
                '%s' => [
                    %s
                ],

EOD;
        return sprintf($template, $name, join(",\n                    ", $definition));
    }

    public function getColumnDefinition($field, $fieldDefinition): string
    {
        $template = <<<EOD
new Column(
                        '%s',
                        [
                            %s
                        ]
                    )
EOD;

        return sprintf($template, $field, join(",\n                            ", $fieldDefinition));
    }

    public function getIndexDefinition($indexName, $indexDefinition, $indexType = null): string
    {
        $template = <<<EOD
new Index('%s', [%s], %s)
EOD;

        return sprintf($template, $indexName, join(", ", $indexDefinition), $indexType ? "'$indexType'" : "''");
    }

    public function getReferenceDefinition($constraintName, $referenceDefinition): string
    {
        $template = <<<EOD
new Reference(
                        '%s',
                        [
                            %s
                        ]
                    )
EOD;

        return sprintf($template, $constraintName, join(",\n                            ", $referenceDefinition));
    }
}
