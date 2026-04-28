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

namespace Phalcon\Migrations\Db;

use Generator;
use PDO;
use PDOStatement;
use Phalcon\Migrations\Exception\InvalidArgumentException;
use Phalcon\Migrations\Observer\Profiler;
use Phalcon\Migrations\Utils\Config;

use function is_int;

final class Connection
{
    private ?PDO $pdo      = null;
    private ?Profiler $profiler = null;
    /** @var callable|null */
    private $logger = null;

    private function __construct(
        private readonly string $dsn,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly array $options,
        private readonly array $queries,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        $driver  = strtolower((string) $config->adapter);
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $queries = [];

        switch ($driver) {
            case 'mysql':
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8',
                    $config->host,
                    $config->port ?? 3306,
                    $config->dbname
                );
                break;

            case 'postgresql':
                $dsn = sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    $config->host,
                    $config->port ?? 5432,
                    $config->dbname
                );
                if ($config->schema !== null) {
                    $queries[] = 'SET search_path TO "' . $config->schema . '"';
                }

                break;

            case 'sqlite':
                $dsn = 'sqlite:' . $config->dbname;
                break;

            default:
                throw InvalidArgumentException::unsupportedDatabaseAdapter($driver);
        }

        return new self($dsn, $config->username, $config->password, $options, $queries);
    }

    public function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        $this->pdo = new PDO($this->dsn, $this->username, $this->password, $this->options);

        foreach ($this->queries as $query) {
            $this->pdo->exec($query);
        }
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function getDriverName(): string
    {
        return (string) $this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function execute(string $sql, array $values = []): void
    {
        $this->log($sql);
        $this->perform($sql, $values);
    }

    public function fetchAll(string $sql, array $values = []): array
    {
        $this->log($sql);

        return $this->perform($sql, $values)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $values = []): array
    {
        $this->log($sql);
        $result = $this->perform($sql, $values)->fetch(PDO::FETCH_ASSOC);

        return is_array($result) ? $result : [];
    }

    public function fetchColumn(string $sql, array $values = [], int $column = 0): array
    {
        $this->log($sql);

        return $this->perform($sql, $values)->fetchAll(PDO::FETCH_COLUMN, $column);
    }

    public function fetchValue(string $sql, array $values = []): mixed
    {
        $this->log($sql);

        return $this->perform($sql, $values)->fetchColumn(0);
    }

    public function fetchPairs(string $sql, array $values = []): array
    {
        $this->log($sql);

        return $this->perform($sql, $values)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function iterate(string $sql, array $values = []): Generator
    {
        $this->log($sql);
        $sth = $this->perform($sql, $values);
        $sth->setFetchMode(PDO::FETCH_ASSOC);

        while ($row = $sth->fetch()) {
            yield $row;
        }
    }

    public function begin(): void
    {
        $this->pdo()->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo()->commit();
    }

    public function rollback(): void
    {
        $this->pdo()->rollBack();
    }

    public function quote(string $value): string
    {
        return (string) $this->pdo()->quote($value);
    }

    public function quoteIdentifier(string $name): string
    {
        $driver = $this->getDriverName();

        if ($driver === 'mysql') {
            return '`' . str_replace('`', '``', $name) . '`';
        }

        return '"' . str_replace('"', '""', $name) . '"';
    }

    public function setLogger(?callable $logger): void
    {
        $this->logger = $logger;
    }

    public function setProfiler(?Profiler $profiler): void
    {
        $this->profiler = $profiler;
    }

    private function pdo(): PDO
    {
        $this->connect();

        assert($this->pdo !== null);

        return $this->pdo;
    }

    private function perform(string $sql, array $values): PDOStatement
    {
        $start = microtime(true);
        $this->profiler?->start($sql, $start);

        $sth = $this->pdo()->prepare($sql);
        foreach ($values as $key => $value) {
            $placeholder = is_int($key) ? $key + 1 : $key;
            $sth->bindValue($placeholder, $value);
        }

        $sth->execute();

        $this->profiler?->end($sql, $start, microtime(true));

        return $sth;
    }

    private function log(string $sql): void
    {
        if ($this->logger !== null) {
            ($this->logger)($sql);
        }
    }
}
