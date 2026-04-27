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

namespace Phalcon\Migrations\Utils;

final class Config
{
    private function __construct(
        public readonly ?string $adapter              = null,
        public readonly ?string $dbname               = null,
        public readonly ?string $descr                = null,
        public readonly array   $exportDataFromTables = [],
        public readonly ?string $host                 = null,
        public readonly bool    $logInDb              = false,
        public readonly ?string $migrationsDir        = null,
        public readonly bool    $migrationsTsBased    = false,
        public readonly bool    $noAutoIncrement      = false,
        public readonly ?string $password             = null,
        public readonly ?int    $port                 = null,
        public readonly ?string $schema               = null,
        public readonly bool    $skipForeignChecks    = false,
        public readonly bool    $skipRefSchema        = false,
        public readonly ?string $username             = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $db  = $data['database']    ?? [];
        $app = $data['application'] ?? [];

        $exportDataFromTables = $app['exportDataFromTables'] ?? [];
        if (is_string($exportDataFromTables)) {
            $exportDataFromTables = explode(',', $exportDataFromTables);
        }

        return new self(
            adapter:              $db['adapter']   ?? null,
            dbname:               $db['dbname']    ?? null,
            descr:                $app['descr']    ?? null,
            exportDataFromTables: $exportDataFromTables,
            host:                 $db['host']      ?? null,
            logInDb:              (bool) ($app['logInDb']             ?? false),
            migrationsDir:        $app['migrationsDir']               ?? null,
            migrationsTsBased:    (bool) ($app['migrationsTsBased']   ?? false),
            noAutoIncrement:      (bool) ($app['no-auto-increment']   ?? false),
            password:             $db['password']  ?? null,
            port:                 isset($db['port']) ? (int) $db['port'] : null,
            schema:               $db['schema']    ?? null,
            skipForeignChecks:    (bool) ($app['skip-foreign-checks'] ?? false),
            skipRefSchema:        (bool) ($app['skip-ref-schema']     ?? false),
            username:             $db['username']  ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter(
            [
                'adapter'  => $this->adapter,
                'dbname'   => $this->dbname,
                'host'     => $this->host,
                'password' => $this->password,
                'port'     => $this->port,
                'schema'   => $this->schema,
                'username' => $this->username,
            ],
            static fn($value) => $value !== null
        );
    }
}