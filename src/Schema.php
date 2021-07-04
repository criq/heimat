<?php

namespace Heimat;

use Katu\Types\TJSON;

abstract class Schema
{
	const CACHE_TIMEOUT = '1 week';

	abstract public static function getWikidataClass() : string;

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

	public function getName($lookup = false) : ?string
	{
		return $this->name ?: ($lookup ? $this->getWikidataTitle() : null);
	}

	public function getData() : ?array
	{
		return $this->data;
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
			return \Katu\Cache\General::get([__CLASS__, __FUNCTION__, $this->getWikidataReference()], '1 week', function () {
				$articles = $this->getWikidataArticles();

				if (count($articles) == 1) {
					return $articles[0]['title'];
				}

				foreach ($articles as $article) {
					$json = \Heimat\Sources\Wikidata\Article::getJSON($article['title']);
					foreach ($json->getArray()['claims']['P31'] as $claim) {
						if (static::getWikidataClass() == $claim['mainsnak']['datavalue']['value']['id']) {
							return $article['title'];
						}
					}
				}

				return null;
			});
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

	public function getWikidataTitle(?string $languageCode = 'cs') : ?string
	{
		return $this->getWikidataArticleJSON()->getArray()['labels'][$languageCode]['value'] ?? null;
	}

	public function getWikipediaArticleTitle(?string $languageCode = 'cs') : ?string
	{
		return $this->getWikidataArticleJSON()->getArray()['sitelinks'][mb_strtolower($languageCode) . 'wiki']['title'] ?? null;
	}

	public function getWikipediaArticleContents()
	{
		return \Heimat\Sources\Wikipedia\Article::getContents($this->getWikipediaArticleTitle());
	}
}