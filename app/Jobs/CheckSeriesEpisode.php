<?php

namespace App\Jobs;

use App\Models\Content;
use Illuminate\Bus\Queueable;
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
        $series = Content::with(['platform', 'server'])->where('media_type', 'series')->get();

        if(count($series) > 0) {

            $count = 0;
            foreach ($series as $latest) {
                $server = $latest->server;
                $host = $server->ssh_host_name;
                $user = $server->ssh_user_name;
                $password = $server->ssh_password;
                $path = $latest->folder_path;

                $content_url = $latest->platform->domain . $latest->url;

                if ($latest->platform->domain === 'https://akw.to/') {
                    $urls = $this->fetchUrls($content_url);
                    Log::info('total-url', [$urls]);
                    return $download = $this->downloadEpisodes($host, $user, $password, $path, $urls);
                }
                $count++;
            }
            Log::info('series-check-count', [$count]);
        }
        return 'Series Not Found';
    }

    private function fetchUrls($content_url) {
        $process = new Process(["node", "scrape.js", $content_url, 'latest', 'series']);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(3600);
        $process->run();
        if ($process->isSuccessful()) {
            $output = $process->getOutput();
            Log::info('total-output', [$output]);
            return $links = json_decode($output, true);
        }
        Log::info('error-message', [$process->getErrorOutput()]);
        return false;
    }

    private function downloadEpisodes($host, $user, $password, $path, $urls)
    {
        if($urls !== null && count($urls) > 0) {
            foreach ($urls as $link) {
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
                $download->run();

                Log::info('download-error', [$download->getErrorOutput()]);
                return true;
            }
        }
        return false;
    }
}
