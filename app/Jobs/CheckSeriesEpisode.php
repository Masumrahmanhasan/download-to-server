<?php

namespace App\Jobs;

use App\Models\Content;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CheckSeriesEpisode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $series = Content::with(['platform', 'server'])->where('media_type' , 'series')->get();
        Log::info($series);
        foreach ($series as $latest) {
            $server = $latest->server;
            $host = $server->ssh_host_name;
            $user = $server->ssh_user_name;
            $password = $server->ssh_password;
            $path = $latest->folder_path;

            $content_url = $latest->platform->domain . $latest->url;

            if ($latest->platform->domain === 'https://akw.to/') {
                $process = new Process(["node", "scrape.js", $content_url, 'latest', 'series']);
                $process->setWorkingDirectory(base_path());
                $process->run();

                if ($process->isSuccessful()) {
                    $output = $process->getOutput();
                    $links = json_decode($output, true);

                    Log::info($links);
                    foreach ($links as $link) {
                        $download = new Process(
                            [
                                'node',
                                'download.js',
                                '--host=' . $host,
                                '--username=' . $user,
                                '--password=' . $password,
                                '--path=' . $path,
                                '--content=' . $link,
                            ]
                        );
                        $download->setWorkingDirectory(base_path());
                        $download->setPty(true);
                        $download->setTimeout(3600);
                        $download->enableOutput();
                        $download->run(function ($type, $buffer) {
                            if (Process::ERR === $type) {
                                echo 'ERR > ' . $buffer;
                            } else {
                                echo 'OUT > ' . $buffer;
                            }
                        });

                    }

                } else {
                    // Handle the case when the request was not successful
                    $output = $process->getErrorOutput();
                    Log::info($output);
                    return 1;
                }
            }
            return 'series latest episode downloaded for akwm';

        }

        return 'series latest episode downloaded';
    }
}
