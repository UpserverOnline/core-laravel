<?php

namespace UpserverOnline\Core\Tests;

use UpserverOnline\Core\Process;
use UpserverOnline\Core\Tests\TestCase;

class ProcessTest extends TestCase
{
    /** @test */
    public function it_returns_the_process_output_as_a_collection_of_line()
    {
        $output = (new Process)->run('uname -a');

        $this->assertCount(1, $output);
    }
}
