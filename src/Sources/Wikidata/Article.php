<?php

namespace Heimat\Sources\Wikidata;

use Katu\Tools\Calendar\Timeout;
use Katu\Types\TIdentifier;
use Katu\Types\TJSON;

class Article extends \Heimat\Source
{
	public static function getSearchResult(string $query) : array
	{
		$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout(static::CACHE_TIMEOUT), function ($query) {
			$curl = new \Curl\Curl;
			$res = $curl->get("https://www.wikidata.org/w/api.php", [
				"action" => "query",
				"format" => "json",
				"list" => "search",
				"srsearch" => $query,
			]);

			return \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));
		});
		$cache->setArgs($query);
		$cache->disableMemory();

		return $cache->getResult();
	}

	public static function getJSON(string $title) : TJSON
	{
		$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout(static::CACHE_TIMEOUT), function ($title) {
			$curl = new \Curl\Curl;
			$res = $curl->get("https://www.wikidata.org/w/api.php", [
				"action" => "query",
				"format" => "json",
				"prop" => "revisions",
				"rvprop" => "content",
				"rvslots" => "*",
				"titles" => $title,
			]);

			$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

			return new TJSON(array_values($res["query"]["pages"])[0]["revisions"][0]["slots"]["main"]["*"]);
		});
		$cache->setArgs($title);
		$cache->disableMemory();

		return $cache->getResult();
	}
}
