<?php

namespace Heimat\Sources\Wikipedia;

use Katu\Tools\DateTime\Timeout;
use Katu\Types\TIdentifier;

class Article extends \Heimat\Source
{
	public static function getQueryResult(string $query) : array
	{
		$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout(static::CACHE_TIMEOUT), function ($query) {
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
		});
		$cache->setArgs($query);
		$cache->disableMemory();

		return $cache->getResult();
	}

	public static function getContents(string $title) : ?string
	{
		try {
			return array_values(static::getQueryResult($title)['query']['pages'])[0]['revisions'][0]['slots']['main']['*'];
		} catch (\Throwable $e) {
			return null;
		}
	}
}
