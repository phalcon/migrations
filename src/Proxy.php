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

namespace Phalcon\Migrations;

use InvalidArgumentException;
use Phalcon\Config;

class Proxy
{
    private $target;
    private $dry;

    public function __construct($target, $dry = false)
    {
        $this->target = $target;
        $this->dry = $dry;
    }

    protected function log($line)
    {
        echo "[" . $line . "]\r\n";
    }
    protected function getTarget()
    {
        return $this->target;
    }
    public function __set($name, $value)
    {
        $this->target->$name = $value;
    }

    public function __get($name)
    {
        return $this->target->$name;
    }

    public function __isset($name)
    {
        return isset($this->target->$name);
    }

    public function __call($name, $arguments)
    {
        if ($this->dry && in_array($name, [
                'addColumn', 'addForeignKey', 'addIndex', 'addPrimaryKey',
                'createTable', 'createView', 'dropColumn', 'dropForeignKey',
                'dropIndex', 'dropPrimaryKey', 'dropTable', 'dropView', 'modifyColumn'
            ])) {
            $dialect = $this->target->getDialect();
            if (method_exists($dialect, $name)) {
                $this->log(call_user_func_array(array($dialect, $name), $arguments));
                return true;
            }
        }
        return call_user_func_array(array($this->target, $name), $arguments);
    }
}
