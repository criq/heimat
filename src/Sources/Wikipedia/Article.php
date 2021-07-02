<?php

namespace Heimat\Sources\Wikipedia;

class Article
{
	public static function getQueryResult(string $query) : array
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

	public static function getArticleContents(string $query) : ?string
	{
		try {
			return array_values(static::getQuery($query)['query']['pages'])[0]['revisions'][0]['slots']['main']['*'];
		} catch (\Throwable $e) {
			return null;
		}
	}
}
