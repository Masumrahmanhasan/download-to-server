<?php

namespace App\Http\Controllers;

use App\Jobs\DownloadContent;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Content;
use App\Models\Platform;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Process\Process;

class ContentController extends Controller
{
    public function index()
    {
        return Inertia::render(
            'Contents/Index',
            [
                'contents' => auth()->user()->contents()->with('platform', 'server')->get(),
            ]
        );
    }

    public function create()
    {
        return Inertia::render(
            'Contents/Create',
            [
                'platforms' => Platform::all(),
                'servers' => Server::all(),

            ]
        );
    }

    public function show(Request $request)
    {
        return Inertia::render(
            'Contents/Edit',
            [
                'content' => Content::with("platform", "server")->find($request->id),
                'platforms' => Platform::all(),
                'servers' => Server::all(),
            ]
        );

    }

    public function delete(Request $request)
    {
        $content = Content::find($request->id);

        $content->delete();

        return Redirect::to('/contents');
    }

    public function store(Request $request)
    {
        $content = Content::query()->create([
            'user_id' => auth()->user()->id,
            'content_name' => $request->content_name,
            'platform_id' => $request->platform_id,
            'server_id' => $request->server_id,
            'url' => $request->url,
            'folder_path' => $request->folder_path,
            'media_type' => $request->media_type,
            'download_type' => $request->download_type
        ]);

        DownloadContent::dispatch($content);

        return Redirect::to('/contents');
    }

    public function getAllEpisodes($data)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $htmlData = $response;

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($htmlData);

        libxml_use_internal_errors(false);

        $id = 'series-episodes'; // Replace with the desired ID

        $element = $dom->getElementById($id);

        $urls = array();
        if ($element !== null) {
            // Get the inner HTML of the matched element
            $xpath = new DOMXPath($dom);
            $episode_divs = $xpath->query("//div[contains(@class, 'bg-primary2')]");

            foreach ($episode_divs as $episode_div) {
                // Extract the episode title and URL
                $title_element = $xpath->query(".//h2/a", $episode_div)->item(0);
                $title = $title_element->nodeValue;
                $url = $title_element->getAttribute("href");

                // Extract the episode date
                $date_element = $xpath->query(".//p[contains(@class, 'entry-date')]", $episode_div)->item(0);
                $date = $date_element->nodeValue;

                // Extract the episode image URL
                $image_element = $xpath->query(".//img[contains(@class, 'img-fluid')]", $episode_div)->item(0);
                $image_url = $image_element->getAttribute("src");

                array_push($urls, $url);
            }
        }

        return $urls;

    }

    public function getSecondLink($urls)
    {
        $redirectUrl = array();
        foreach ($urls as $item) {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $item);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $htmlData = $response;
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($htmlData);

            libxml_use_internal_errors(false);
            $id = 'tab-4'; // Replace with the desired ID
            $element = $dom->getElementById($id);
            $xpath2 = new DOMXPath($dom);
            $url_single = $xpath2->query(".//a[contains(@class, 'link-download')]");

            foreach ($url_single as $link) {

                $url = $link->getAttribute("href");
                array_push($redirectUrl, $url);
                break;
            }
        }

       return $redirectUrl;
    }

    public function getThirdLink($urls)
    {
        $redirectUrl = array();
        foreach ($urls as $item) {

            $client = new Client();
            $response = $client->request('GET', $item);
            $htmlData = $response->getBody()->getContents();
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($htmlData);

            libxml_use_internal_errors(false);

            $xpath2 = new DOMXPath($dom);
            $url_single = $xpath2->query(".//div[contains(@class, 'content')]");

            foreach ($url_single as $link) {
                $third = $xpath2->query("//a[contains(@class, 'download-link')]", $link);
                foreach ($third as $main_url) {
                    $url = $main_url->getAttribute("href");
                    array_push($redirectUrl, $url);
                    break;
                }

            }
        }
        return array_values(array_unique($redirectUrl));
    }

    public function getFourthLink($urls)
    {
        $download_urls = array();
        foreach ($urls as $item) {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $item);
            $htmlData = $response->getBody()->getContents();

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($htmlData);
            libxml_use_internal_errors(false);

            $xpath = new DOMXPath($dom);
            $btnLoaderElements = $xpath->query("//*[contains(@class, 'btn-loader')]");

            if ($btnLoaderElements->length > 0) {
                $btnLoaderElement = $btnLoaderElements[0]; // Assuming you only want the first matching element

                $aTags = $xpath->query("//a[contains(@class, 'link')]", $btnLoaderElement);
                array_push($download_urls,$aTags[0]->getAttribute("href"));
            } else {
                echo "Element with class 'btn-loader' not found.";
            }
        }


        return $download_urls;
    }

    public function update(Request $request, Content $content)
    {
        $contents = Content::find($request->id);

        $contents->content_name = $request->content_name;
        $contents->platform_id = $request->platform_id;
        $contents->server_id = $request->server_id;
        $contents->url = $request->url;
        $contents->media_type = $request->media_type;
        $contents->save();

        return Redirect::to('/contents');
    }

}
