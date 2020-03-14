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
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Filter;
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Console\Path;

abstract class Command implements CommandsInterface
{
    /**
     * Events Manager
     *
     * @var EventsManager
     */
    protected $eventsManager;

    /**
     * Output encoding of the script.
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Parameters received by the script.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var Path
     */
    protected $path;

    /**
     * @param Parser $parser
     * @param EventsManager $eventsManager
     */
    final public function __construct(Parser $parser, EventsManager $eventsManager)
    {
        $this->parser = $parser;
        $this->parameters = $parser->getParsedCommands();
        $this->eventsManager = $eventsManager;
        $this->path = new Path();
    }

    /**
     * Events Manager
     *
     * @param EventsManager $eventsManager
     */
    public function setEventsManager(EventsManager $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * Returns the events manager
     *
     * @return EventsManager
     */
    public function getEventsManager(): EventsManager
    {
        return $this->eventsManager;
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
                if (file_exists($path . $configPath . "config." . $extension)) {
                    return $this->loadConfig($path . $configPath . "/config." . $extension);
                }
            }
        }

        $directory = new \RecursiveDirectoryIterator('.');
        $iterator = new \RecursiveIteratorIterator($directory);
        foreach ($iterator as $f) {
            if (preg_match('/config\.(php|ini|json|yaml|yml)$/i', $f->getPathName())) {
                return $this->loadConfig($f->getPathName());
            }
        }

        throw new CommandsException("Builder can't locate the configuration file.");
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
            throw new CommandsException("Config file extension not found.");
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
     * Sets the output encoding of the script.
     * @param string $encoding
     *
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * Returns all received options.
     *
     * @param mixed $filters Filter name or array of filters [Optional]
     *
     * @return array
     */
    public function getOptions($filters = null): array
    {
        if (!$filters) {
            return $this->parameters;
        }

        $result = [];
        foreach ($this->parameters as $param) {
            $result[] = $this->filter($param, $filters);
        }

        return $result;
    }

    /**
     * Returns the value of an option received.
     *
     * @param mixed $option Option name or array of options
     * @param mixed $filters Filter name or array of filters [Optional]
     * @param mixed $defaultValue Default value [Optional]
     *
     * @return mixed
     */
    public function getOption($option, $filters = null, $defaultValue = null)
    {
        if (is_array($option)) {
            foreach ($option as $optionItem) {
                if (isset($this->parameters[$optionItem])) {
                    if ($filters !== null) {
                        return $this->filter($this->parameters[$optionItem], $filters);
                    }

                    return $this->parameters[$optionItem];
                }
            }

            return $defaultValue;
        }

        if (isset($this->parameters[$option])) {
            if ($filters !== null) {
                return $this->filter($this->parameters[$option], $filters);
            }

            return $this->parameters[$option];
        }

        return $defaultValue;
    }

    /**
     * Indicates whether the script was a particular option.
     *
     * @param string|string[] $option
     * @return bool
     */
    public function isReceivedOption($option): bool
    {
        if (!is_array($option)) {
            $option = [$option];
        }

        foreach ($option as $op) {
            if (in_array($op, $this->parameters)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filters a value
     *
     * @param mixed $paramValue
     * @param array $filters
     *
     * @return mixed
     */
    protected function filter($paramValue, $filters)
    {
        $filter = new Filter();

        return $filter->sanitize($paramValue, $filters);
    }

    /**
     * Prints the available options in the script
     *
     * @param array $parameters
     */
    public function printParameters($parameters): void
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
            print Color::colorize("    " . $description) . PHP_EOL;
        }
    }

    /**
     * Returns the processed parameters
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function canBeExternal(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function hasIdentifier($identifier): bool
    {
        return in_array($identifier, $this->getCommands(), true);
    }
}
