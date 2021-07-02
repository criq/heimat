<?php

namespace Heimat\Sources\Wikidata;

use Katu\Types\TJSON;

class Article
{
	public static function getSearchResult(string $query) : array
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($query) {
			$curl = new \Curl\Curl;
			$res = $curl->get('https://www.wikidata.org/w/api.php', [
				'action' => 'query',
				'format' => 'json',
				'list' => 'search',
				'srsearch' => $query,
			]);

			return \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));
		}, $query);
	}

	public static function getJSON(string $title) : TJSON
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($title) {
			$curl = new \Curl\Curl;
			$res = $curl->get('https://www.wikidata.org/w/api.php', [
				'action' => 'query',
				'format' => 'json',
				'prop' => 'revisions',
				'rvprop' => 'content',
				'rvslots' => '*',
				'titles' => $title,
			]);

			$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

			return new TJSON(array_values($res['query']['pages'])[0]['revisions'][0]['slots']['main']['*']);
		}, $title);
	}
}
