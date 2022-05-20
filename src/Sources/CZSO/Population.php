<?php

// https://www.risy.cz/cs/vyhledavace/obce/532606-choratice

namespace Heimat\Sources\CZSO;

use Katu\Tools\Calendar\Timeout;
use Katu\Types\TURL;

class Population extends \Heimat\Source
{
	public static function getTableUrls(?int $year = null) : array
	{
		if (!$year) {
			$year = date('Y');
		}

		$url = "https://www.czso.cz/csu/czso/pocet-obyvatel-v-obcich-k-11" . $year;
		$src = \Katu\Cache\URL::get(new TURL($url), new Timeout(static::CACHE_TIMEOUT));
		$dom = \Katu\Tools\DOM\DOM::crawlHtml($src);

		$array = $dom->filter('.prilohy-publikace tr')->each(function ($e) {
			return [
				'title' => $e->filter('td')->eq(0)->text(),
				'urls' => $e->filter('td')->eq(1)->filter('a')->each(function ($e) {
					if (preg_match('/Excel/', $e->text())) {
						return new TURL($e->attr('href'));
					}
				}),
			];
		});

		$urls = [];
		foreach ($array as $item) {
			$urls[$item['title']] = array_filter($item['urls'])[0] ?? null;
		}
		$urls = array_filter($urls);

		return $urls;
	}

	public static function getTableUrlByTitle(string $title, ?int $year = null) : ?TURL
	{
		foreach (Population::getTableUrls($year) as $key => $value) {
			if (preg_match("/$title/", $key)) {
				return $value;
			}
		}

		return null;
	}
}
