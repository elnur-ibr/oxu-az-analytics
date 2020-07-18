<?php

namespace App\Http\Controllers;

use App\Article;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArticleController extends Controller
{
    private $month = [
        'İyl' => '07'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $startTimeStamp = now()->timestamp;
        $endTimeStamp = now()->subDays(7)->startOfDay()->timestamp;
        //dd($endTimeStamp->timestamp);
        //dd(Carbon::parse("$year-$month-$day $time")->timestamp);

        $data = collect();

        for($currenTimeStamp = $startTimeStamp; $currenTimeStamp > $endTimeStamp;)
        {
            $doc = new \DOMDocument();
            $doc->loadHTML($this->getHTML($currenTimeStamp));

            $newsList = $doc->getElementsByTagName('section')->item(1)->childNodes;

            for($i=0; $i < $newsList->length; $i++)
            {
                $news = $newsList->item($i);

                if($news->getAttribute('class') == 'news-i') {
                    $newsInfo = [];
                    for($x=0; $x < $news->childNodes->length; $x++)
                    {
                        $node = $news->childNodes->item($x);
                        if(!method_exists($node, 'getAttribute'))
                            continue;

                        //If link
                        if($node->getAttribute('class') == 'news-i-inner') {
                            $newsInfo['base_url'] = 'https://oxu.az';
                            $newsInfo['url'] = $node->getAttribute('href');
                            $newsInfo['title'] = $node->childNodes->item(1)->childNodes->item(1)->nodeValue;

                            $newsInfo['published'] = $this->getPublishDate($node);
                        }

                        //If stats
                        elseif($node->getAttribute('class') == 'stats') {
                            $newsInfo['view'] = (integer) $node->lastChild->firstChild->nodeValue;
                        };
                    }

                    $data->push($newsInfo);
                }
            }

            $currenTimeStamp = Carbon::parse($data->last()['published'])->timestamp;

            if($data->count() > 200)
            {
                Article::insert($data->toArray());
                $data = collect();
            }
        }

        Article::insert($data->toArray());

    }

    protected function getHTML($time = null)
    {
        $url = 'https://oxu.az/?cursor=' . ($time ?? (integer) now());
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($handle);
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
        libxml_use_internal_errors(true); // Prevent HTML errors from displaying

        return $html;
    }

    protected function getPublishDate($node)
    {
        $when = $node->childNodes->item(1)->childNodes->item(0);
        $time = $when->childNodes->item(1)->nodeValue;

        $date = $when->childNodes->item(0);

        $day = $date->childNodes->item(0)->nodeValue;
        $day = (integer) str_replace ('&nbsp', '',$day);

        $month = (string) $date->childNodes->item(1)->nodeValue;
        $month = $this->month[$month];

        $year = (integer) $date->childNodes->item(2)->nodeValue;

        return  "$year-$month-$day $time:00";
    }
}
