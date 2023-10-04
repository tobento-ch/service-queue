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
use Tobento\Service\Queue\Worker;
use Tobento\Service\Queue\WorkerOptions;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\Event;
use Tobento\Service\Event\EventsInterface;

class WorkCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        queue:work | Start processing jobs on the queue(s)
        {--name=default : The name of the worker}
        {--queue= : The name of the queue to work}
        {--memory=128 : The memory limit in megabytes}
        {--timeout=60 : The number of seconds the worker can run}
        {--sleep=3 : The number of seconds to sleep when no job is available}
        {--max-jobs=0 : The number of jobs to process before stopping}
        {--stop-when-empty : Stops the worker when the queue is empty}
    ';
    
    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param Worker $worker
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(InteractorInterface $io, Worker $worker): int
    {
        if ($worker->eventDispatcher() instanceof EventsInterface) {
            $this->listenForEvents($worker->eventDispatcher(), $io);
        }
        
        $worker->run(
            // specify the name of the queue you wish to use.
            // If null, it uses all queues by its priority, highest first.
            queue: $io->option(name: 'queue'), // null|string

            // specify the options:
            options: new WorkerOptions(
                name: $io->option(name: 'name'),
                
                // The maximum amount of RAM the worker may consume:
                memory: intval($io->option(name: 'memory')),

                // The maximum number of seconds a worker may run:
                timeout: intval($io->option(name: 'timeout')),

                // The number of seconds to wait in between polling the queue:
                sleep: intval($io->option(name: 'sleep')),

                // The maximum number of jobs to run, 0 (unlimited):
                maxJobs: intval($io->option(name: 'max-jobs')),

                // Indicates if the worker should stop when the queue is empty:
                stopWhenEmpty: $io->option(name: 'stop-when-empty'),
            ),
        );
        
        return 0;
    }
    
    /**
     * Listen for events.
     *
     * @param EventsInterface $events
     * @param InteractorInterface $io
     * @return void
     */    
    protected function listenForEvents(EventsInterface $events, InteractorInterface $io): void
    {
        $events->listen(function(Event\WorkerStarting $event) use ($io) {
            $io->info(sprintf('Worker %s starting', $event->options()->name()));
        });
        
        $events->listen(function(Event\WorkerStopped $event) use ($io) {
            $io->info(sprintf('Worker %s stopped', $event->options()->name()));
        });
        
        $events->listen(function(Event\JobStarting $event) use ($io) {
            $job = $event->job();
            $io->info(sprintf('Starting job %s with the id %s', $job->getName(), $job->getId()));
        });

        $events->listen(function(Event\JobFinished $event) use ($io) {
            $job = $event->job();
            $info = '';
            
            if ($job->parameters()->has(Parameter\Monitor::class)) {
                $monitor = $job->parameters()->get(Parameter\Monitor::class);
                
                $info = sprintf(
                    ', runtime in seconds: %s, memory usage in bytes: %s',
                    $monitor->runtimeInSeconds(),
                    $monitor->memoryUsage()
                );
            }
            
            $io->success(sprintf('Finished job %s with the id %s', $job->getName(), $job->getId()).$info);
        });
        
        $events->listen(function(Event\JobFailed $event) use ($io) {
            $job = $event->job();
            $io->error(sprintf('Failed job %s with the id %s', $job->getName(), $job->getId()));
        });
        
        $events->listen(function(Event\PoppingJobFailed $event) use ($io) {
            $io->error(sprintf(
                'Popping failed on queue %s with message: %s',
                (string)$event->queue(),
                $event->exception()->getMessage()
            ));
        });
    }
}