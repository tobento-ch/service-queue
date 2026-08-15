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

namespace Tobento\Service\Queue\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Console\InteractorInterface;
use Tobento\Service\Queue\Console\ListenCommand;

class ListenCommandTest extends TestCase
{
    public function testBuildWorkCommandContainsExpectedOptions()
    {
        $command = new class extends ListenCommand {
            public static array $captured = [];

            public function handle(InteractorInterface $io): int
            {
                static::$captured = $this->buildWorkCommand($io);

                return 0;
            }
        };

        new TestCommand(
            command: $command,
            input: [
                '--name' => 'reports',
                '--memory' => '256',
                '--timeout' => '30',
                '--sleep' => '1',
                '--max-jobs' => '5',
                '--queue' => 'primary',
            ],
        )
        ->expectsExitCode(0)
        ->execute();

        $captured = $command::$captured;

        $this->assertContains('queue:work', $captured);
        $this->assertContains('--name=reports', $captured);
        $this->assertContains('--memory=256', $captured);
        $this->assertContains('--timeout=30', $captured);
        $this->assertContains('--sleep=1', $captured);
        $this->assertContains('--max-jobs=5', $captured);
        $this->assertContains('--stop-when-empty', $captured);
        $this->assertContains('--queue=primary', $captured);
    }

    public function testBuildWorkCommandOmitsQueueOptionWhenNotSet()
    {
        $command = new class extends ListenCommand {
            public static array $captured = [];

            public function handle(InteractorInterface $io): int
            {
                static::$captured = $this->buildWorkCommand($io);

                return 0;
            }
        };

        new TestCommand(command: $command)
        ->expectsExitCode(0)
        ->execute();

        foreach ($command::$captured as $arg) {
            $this->assertStringStartsNotWith('--queue=', $arg);
        }
    }
}