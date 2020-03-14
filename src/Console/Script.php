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

namespace Phalcon\Migrations\Console;

use DirectoryIterator;
use Phalcon\Migrations\Console\Commands\Command;
use Phalcon\Migrations\Console\Commands\CommandsException;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Migrations\Script\ScriptException;

/**
 * Component that allows you to write scripts to use CLI.
 */
class Script
{
    /**
     * Events Manager
     *
     * @var EventsManager
     */
    protected $eventsManager;

    /**
     * Commands attached to the Script
     *
     * @var Command[]
     */
    protected $commands;

    /**
     * Script Constructor
     *
     * @param EventsManager $eventsManager
     */
    public function __construct(EventsManager $eventsManager)
    {
        $this->commands = [];
        $this->eventsManager = $eventsManager;
    }

    /**
     * Events Manager
     *
     * @param EventsManager $eventsManager
     */
    public function setEventsManager(EventsManager $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * Returns the events manager
     *
     * @return EventsManager
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }

    /**
     * Adds commands to the Script
     *
     * @param Command $command
     */
    public function attach(Command $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Returns the commands registered in the script
     *
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Dispatch the Command
     *
     * @param Command $command
     * @return bool
     */
    public function dispatch(Command $command): bool
    {
        // If beforeCommand fails abort
        if ($this->eventsManager->fire('command:beforeCommand', $command) === false) {
            return false;
        }

        // If run the commands fails abort too
        try {
            $return = true;
            $command->run($command->getParameters());
        } catch (BuilderException $builderException) {
            echo Color::error($builderException->getMessage());
            $return = false;
        } catch (CommandsException $commandsException) {
            echo Color::error($commandsException->getMessage());
            $return = false;
        }

        $this->eventsManager->fire('command:afterCommand', $command);

        return $return;
    }

    /**
     * Run the scripts
     */
    public function run()
    {
        return $this->dispatch($this->commands[0]);
    }
}
