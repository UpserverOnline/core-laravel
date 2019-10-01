<?php

namespace UpserverOnline\Core\Commands;

use Illuminate\Console\Command;
use UpserverOnline\Core\Upserver;

class VerifyConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upserver:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifies the Upserver.online configuration';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (Upserver::application()->isSuccessful()) {
            return $this->info('Application authentication with Upserver.online succeeded.');
        }

        $this->error('Application authentication with Upserver.online failed.');
    }
}
