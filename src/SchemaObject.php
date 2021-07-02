<?php

namespace Heimat;

use Katu\Files\Formats\JSON;
use Katu\Types\TJSON;

class SchemaObject
{
	public function __construct(string $reference, ?string $name = null, ?array $data = null)
	{
		$this->reference = $reference;
		$this->name = $name;
		$this->data = $data;
	}

	public function getReference() : string
	{
		return $this->reference;
	}

	public function getName() : ?string
	{
		return $this->name;
	}

	public function getWikidataArticleTitle() : ?string
	{
		try {
			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($reference) {
				$curl = new \Curl\Curl;
				$res = $curl->get('https://www.wikidata.org/w/api.php', [
					'action' => 'query',
					'format' => 'json',
					'list' => 'search',
					'srsearch' => '"' . $reference . '"',
				]);
				$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

				return $res['query']['search'][0]['title'] ?: null;
			}, $this->getReference());
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataArticleContents() : TJSON
	{
		try {
			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($reference) {
				$curl = new \Curl\Curl;
				$res = $curl->get('https://www.wikidata.org/w/api.php', [
					'action' => 'query',
					'format' => 'json',
					'prop' => 'revisions',
					'rvprop' => 'content',
					'rvslots' => '*',
					'titles' => $this->getWikidataArticleTitle(),
				]);
				$res = \Katu\Files\Formats\JSON::decodeAsArray(\Katu\Files\Formats\JSON::encode($res));

				return new TJSON(array_values($res['query']['pages'])[0]['revisions'][0]['slots']['main']['*']);
			}, $this->getReference());
		} catch (\Throwable $e) {
			return null;
		}
	}
}
