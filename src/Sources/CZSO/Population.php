<?php

namespace Heimat\Sources\CZSO;

class Population
{
	public static function getTableUrls(?int $year = null) : array
	{
		if (!$year) {
			$year = date('Y');
		}

		$url = "https://www.czso.cz/csu/czso/pocet-obyvatel-v-obcich-k-11" . $year;
		$src = \Katu\Cache\URL::get($url, '1 day');
		$dom = \Katu\Tools\DOM\DOM::crawlHtml($src);

		$array = $dom->filter('.prilohy-publikace tr')->each(function ($e) {
			return [
				'title' => $e->filter('td')->eq(0)->text(),
				'urls' => $e->filter('td')->eq(1)->filter('a')->each(function ($e) {
					if (preg_match('/Excel/', $e->text())) {
						return $e->attr('href');
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
}
