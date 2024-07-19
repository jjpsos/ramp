<?php

declare(strict_types=1);

namespace MyApp\Tasks;

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function mainAction()
    {
        // This is the default task and the default action
        echo 'Console commands are working!' . PHP_EOL;
    }
}
