<?php

namespace Heimat\Sources\Wikipedia;

class Settlement
{
	public static function getQuery(string $query) : array
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($query) {
			$curl = new \Curl\Curl;
			$res = $curl->get('https://cs.wikipedia.org/w/api.php', [
				'action' => 'query',
				'format' => 'json',
				'prop' => 'revisions',
				'rvprop' => 'content',
				'rvsection' => '0',
				'rvslots' => '*',
				'titles' => $query,
			]);
			$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

			return $res;
		}, $query);
	}

	public static function getArticle(string $query) : ?string
	{
		try {
			return array_values(static::getQuery($query)['query']['pages'])[0]['revisions'][0]['slots']['main']['*'];
		} catch (\Throwable $e) {
			return null;
		}
	}

	public static function getSettlementArticle(string $name, string $lau2) : ?string
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($name, $lau2) {
			$article = static::getArticle($name);
			if (preg_match("/$lau2/", $article)) {
				return $article;
			}

			$search = \Heimat\Sources\Google\Search::getQuery(implode(' ', [
				$name,
				$lau2,
			]));
			$title = $search['items'][0]['title'];
			if (preg_match('/(?<title>.+) – Wikipedie/', $title, $match)) {
				$article = static::getArticle($match['title']);
				if (preg_match("/$lau2/", $article)) {
					return $article;
				}
			}

			return null;
		}, $name, $lau2);
	}
}
