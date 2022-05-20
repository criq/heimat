<?php

namespace Heimat\Sources\Google;

use Katu\Tools\Calendar\Timeout;
use Katu\Types\TIdentifier;

class Search extends \Heimat\Source
{
	public static function getQueryResult(string $query) : array
	{
		$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout(static::CACHE_TIMEOUT), function ($query) {
			$curl = new \Curl\Curl;
			$res = $curl->get("https://www.googleapis.com/customsearch/v1", [
				"key" => \Katu\Config\Config::get("google", "api", "key"),
				"cx" => "c07f4a4b3753b414b",
				"q" => $query,
			]);
			$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

			return $res;
		});
		$cache->setArgs($query);
		$cache->disableMemory();

		return $cache->getResult();
	}
}
