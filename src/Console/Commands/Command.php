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
use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Console\Path;

abstract class Command implements CommandsInterface
{
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
     */
    final public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        $this->path = new Path();
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
            throw new CommandsException('Config file extension not found.');
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
            print Color::colorize('    ' . $description) . PHP_EOL;
        }
    }
}
