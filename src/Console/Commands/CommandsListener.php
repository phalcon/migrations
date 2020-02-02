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

use Phalcon\Events\Event;

/**
 * Commands Listener
 */
class CommandsListener
{
    /**
     * Before command executing
     *
     * @param Event $event
     * @param Command $command
     *
     * @return bool
     * @throws CommandsException
     */
    public function beforeCommand(Event $event, Command $command): bool
    {
        $parameters = $command->parseParameters([], ['h' => 'help']);

        if (
            count($parameters) < ($command->getRequiredParams() + 1) ||
            $command->isReceivedOption(['help', 'h', '?']) ||
            in_array($command->getOption(1), ['help', 'h', '?'])
        ) {
            $command->getHelp();

            return false;
        }

        return true;
    }
}
