<?php

namespace Heimat;

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

	public function getWikipediaArticle() : ?string
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__], '1 week', function ($name, $reference) {
			$article = \Heimat\Sources\Wikipedia\Article::getArticleContents($name);
			if (preg_match("/$reference/", $article)) {
				return $article;
			}

			$search = \Heimat\Sources\Google\Search::getQueryResult(implode(' ', [
				$name,
				$reference,
			]));
			$title = $search['items'][0]['title'];
			if (preg_match('/(?<title>.+) – Wikipedie/', $title, $match)) {
				$article = \Heimat\Sources\Wikipedia\Article::getArticleContents($match['title']);
				if (preg_match("/$reference/", $article)) {
					return $article;
				}
			}

			return null;
		}, $this->getName(), $this->getReference());
	}
}
