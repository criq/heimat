<?php

namespace Heimat;

use Katu\Types\TJSON;

abstract class SchemaObject
{
	abstract public function getWikidataClass() : string;

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

	public function getWikidataReference() : string
	{
		return $this->getReference();
	}

	public function getWikidataArticles() : ?array
	{
		try {
			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($reference) {
				$res = \Heimat\Sources\Wikidata\Article::getSearchResult('"' . $reference . '"');

				return $res['query']['search'];
			}, $this->getWikidataReference());
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataArticleTitle() : ?string
	{
		try {
			$object = $this;

			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($reference) use ($object) {
				$articles = $object->getWikidataArticles();

				if (count($articles) == 1) {
					return $articles[0]['title'];
				}

				foreach ($articles as $article) {
					$json = \Heimat\Sources\Wikidata\Article::getJSON($article['title']);
					foreach ($json->getArray()['claims']['P31'] as $claim) {
						if ($this->getWikidataClass() == $claim['mainsnak']['datavalue']['value']['id']) {
							return $article['title'];
						}
					}
				}

				return null;
			}, $this->getWikidataReference());
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataArticleJSON() : ?TJSON
	{
		try {
			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($title) {
				return \Heimat\Sources\Wikidata\Article::getJSON($title);
			}, $this->getWikidataArticleTitle());
		} catch (\Throwable $e) {
			return null;
		}
	}

	// public function getNUTS3()
	// {
	// 	return $this->getWikidataArticleJSON()->getArray()['claims']['P605'][0]['mainsnak']['datavalue']['value'] ?? null;
	// 	var_dump($this->getWikidataArticleJSON()->getArray()['claims']['P782'] ?? null);
	// 	var_dump($this->getWikidataArticleJSON()->getArray()['claims']['P281'] ?? null);
	// }
}
