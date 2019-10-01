<?php

namespace UpserverOnline\Core\Tests;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use UpserverOnline\Core\ApiResponse;
use UpserverOnline\Core\Commands\VerifyConfig;
use UpserverOnline\Core\Tests\TestCase;
use UpserverOnline\Core\Upserver;

class VerifyConfigTest extends TestCase
{
    /** @test */
    public function it_succeeds_when_the_api_returns_200()
    {
        Upserver::shouldReceive('application')
            ->andReturn(ApiResponse::fromArray(['data' => ['app_id' => 1]], 200));

        $input = $this->mock(InputInterface::class);
        $input->shouldIgnoreMissing();

        $output = $this->mock(ConsoleOutput::class);
        $output->shouldReceive('getVerbosity')->andReturn(16);
        $output->shouldReceive('getFormatter')->andReturn(new OutputFormatter);
        $output->shouldReceive('writeln')->withArgs(function ($line) {
            return $line === '<info>Application authentication with Upserver.online succeeded.</info>';
        });

        $command = new VerifyConfig;
        $command->setLaravel(app());
        $command->run($input, $output);
    }

    /** @test */
    public function it_fails_when_the_application_fetching_fails()
    {
        Upserver::shouldReceive('application')
            ->andReturn(ApiResponse::fromArray([], 401));

        $input = $this->mock(InputInterface::class);
        $input->shouldIgnoreMissing();

        $output = $this->mock(ConsoleOutput::class);
        $output->shouldReceive('getVerbosity')->andReturn(32);
        $output->shouldReceive('getFormatter')->andReturn(new OutputFormatter);
        $output->shouldReceive('writeln')->withArgs(function ($line) {
            return $line === '<error>Application authentication with Upserver.online failed.</error>';
        });

        $command = new VerifyConfig;
        $command->setLaravel(app());
        $command->run($input, $output);
    }
}
