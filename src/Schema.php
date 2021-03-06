<?php

namespace Heimat;

use Katu\Tools\Calendar\Timeout;
use Katu\Types\TIdentifier;
use Katu\Types\TJSON;

abstract class Schema
{
	const CACHE_TIMEOUT = "2 weeks";

	abstract public static function getWikidataClass(): string;

	public function __construct(string $reference, ?string $name = null, ?array $data = null)
	{
		$this->reference = $reference;
		$this->name = $name;
		$this->data = $data;
	}

	public function getReference(): string
	{
		return $this->reference;
	}

	public function getName($lookup = false): ?string
	{
		return $this->name ?: ($lookup ? $this->getWikidataTitle() : null);
	}

	public function getData(): ?array
	{
		return $this->data;
	}

	public function getWikidataReference(): string
	{
		return $this->getReference();
	}

	public function getWikidataArticles(): ?array
	{
		try {
			$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout("1 week"), function ($reference) {
				$res = \Heimat\Sources\Wikidata\Article::getSearchResult("\"{$reference}\"");

				return $res["query"]["search"];
			});
			$cache->disableMemory();
			$cache->setArgs($this->getWikidataReference());

			return $cache->getResult();
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataArticleTitle(): ?string
	{
		try {
			$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__, $this->getWikidataReference()), new Timeout("1 week"), function () {
				$articles = $this->getWikidataArticles();

				if (count($articles) == 1) {
					return $articles[0]["title"];
				}

				foreach ($articles as $article) {
					$json = \Heimat\Sources\Wikidata\Article::getJSON($article["title"]);
					foreach ($json->getArray()["claims"]["P31"] as $claim) {
						if (static::getWikidataClass() == $claim["mainsnak"]["datavalue"]["value"]["id"]) {
							return $article["title"];
						}
					}
				}

				return null;
			});
			$cache->disableMemory();

			return $cache->getResult();
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataArticleJSON(): ?TJSON
	{
		try {
			$cache = new \Katu\Cache\General(new TIdentifier(__CLASS__, __FUNCTION__), new Timeout("1 week"), function ($title) {
				return \Heimat\Sources\Wikidata\Article::getJSON($title);
			});
			$cache->setArgs($this->getWikidataArticleTitle());
			$cache->disableMemory();

			return $cache->getResult();
		} catch (\Throwable $e) {
			return null;
		}
	}

	public function getWikidataTitle(?string $languageCode = "cs"): ?string
	{
		return $this->getWikidataArticleJSON()->getArray()["labels"][$languageCode]["value"] ?? null;
	}

	public function getWikipediaArticleTitle(?string $languageCode = "cs"): ?string
	{
		return $this->getWikidataArticleJSON()->getArray()["sitelinks"][mb_strtolower($languageCode) . "wiki"]["title"] ?? null;
	}

	public function getWikipediaArticleContents()
	{
		return \Heimat\Sources\Wikipedia\Article::getContents($this->getWikipediaArticleTitle());
	}
}
