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

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class ListenCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        queue:listen | Listen to jobs on the queue(s), restarting the worker process after each job
        {--name=default : The name of the worker}
        {--queue= : The name of the queue to work}
        {--memory=128 : The memory limit in megabytes}
        {--timeout=60 : The number of seconds the worker can run}
        {--sleep=3 : The number of seconds to sleep when no job is available}
        {--max-jobs=1 : The number of jobs to process before restarting the process}
        {--rest=0 : The number of seconds to rest between each restarted process}
    ';

    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @return int The exit status code:
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(InteractorInterface $io): int
    {
        $queueName = $io->option(name: 'queue');
        $queueLabel = $queueName ? sprintf('[%s]', $queueName) : 'all queues';

        $io->info(sprintf('Listening on %s. Press Ctrl+C to stop.', $queueLabel));

        $shouldStop = false;

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);

            $handler = function (int $signal) use ($io, &$shouldStop): void {
                $io->info(sprintf('Received stop signal [%d], finishing current job then exiting…', $signal));
                $shouldStop = true;
            };

            /** @psalm-suppress UndefinedConstant */
            pcntl_signal(SIGINT, $handler);
            /** @psalm-suppress UndefinedConstant */
            pcntl_signal(SIGTERM, $handler);
        }

        while (! $shouldStop) {
            $process = new Process($this->buildWorkCommand($io));
            $process->setTimeout(null);
            $process->start();

            while ($process->isRunning()) {
                $io->write($process->getIncrementalOutput());
                $io->write($process->getIncrementalErrorOutput());
                usleep(100_000);
            }

            if (! $process->isSuccessful()) {
                $io->error('Worker process exited with an error.');
            }

            /** @psalm-suppress TypeDoesNotContainType */
            if ($shouldStop) {
                break;
            }

            $rest = intval($io->option(name: 'rest'));

            if ($rest > 0) {
                sleep($rest);
            }
        }

        $io->info('Listener stopped.');

        return 0;
    }

    /**
     * Build the queue:work command array for a single worker iteration.
     *
     * @param InteractorInterface $io
     * @return array<int, string>
     */
    public function buildWorkCommand(InteractorInterface $io): array
    {
        $phpBinary = new PhpExecutableFinder()->find(false) ?: 'php';
        $consoleBinary = defined('APP_CONSOLE_BIN') ? APP_CONSOLE_BIN : 'ap';

        $command = [
            $phpBinary,
            $consoleBinary,
            'queue:work',
            '--name='.$io->option(name: 'name'),
            '--memory='.intval($io->option(name: 'memory')),
            '--timeout='.intval($io->option(name: 'timeout')),
            '--sleep='.intval($io->option(name: 'sleep')),
            '--max-jobs='.intval($io->option(name: 'max-jobs')),
            '--stop-when-empty',
        ];

        if (! empty($io->option(name: 'queue'))) {
            $command[] = '--queue='.$io->option(name: 'queue');
        }

        return $command;
    }
}