<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Queue\Console;

use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Queue\QueuesInterface;

class ClearCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        queue:clear | Delete all of the jobs from the queue(s)
        {--queue[] : The name of the queues}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param QueuesInterface $queues
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(InteractorInterface $io, QueuesInterface $queues): int
    {
        $queueNames = $io->option(name: 'queue');
        
        if (empty($queueNames)) {
            $queueNames = $queues->names();
        }
        
        foreach($queueNames as $queueName) {
            if (! $queues->has($queueName)) {
                $io->info(sprintf('Queue %s not found to clear jobs', $queueName));
                continue;
            }
            
            if ($queues->queue($queueName)->clear()) {
                $io->success(sprintf('Jobs cleared from queue %s', $queueName));
            } else {
                $io->error(sprintf('Could not clear jobs from queue %s', $queueName));
            }
        }
        
        return 0;
    }
}