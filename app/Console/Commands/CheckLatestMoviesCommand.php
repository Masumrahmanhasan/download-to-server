<?php

namespace App\Console\Commands;

use App\Jobs\CheckLatestMovies;
use Illuminate\Console\Command;

class CheckLatestMoviesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-latest-movies-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'it will check latest movie from the url';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CheckLatestMovies::dispatch();
        $this->info('Movies checked and downloaded successfully.');
    }
}
