<?php

namespace Heimat\Schema;

class LAU1 extends \Heimat\Schema
{
	public static function getList(?int $year = null) : array
	{
		$url = \Heimat\Sources\CZSO\Population::getTableUrlByTitle("Počet obyvatel v regionech soudržnosti, krajích a okresech České republiky", $year);
		if (!$url) {
			return null;
		}

		$file = \Katu\Files\File::createTemporaryFromURL($url, "xlsx");

		$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$worksheet = $xls->getSheet(0);

		$array = $worksheet->toArray();
		$array = array_values(array_filter($array, function ($i) {
			return preg_match("/^CZ[0-9A-Z]{4}$/", $i[0]);
		}));
		$array = array_map(function ($i) {
			return new static($i[0], $i[1], [
				"population" => $i[2],
				"populationMale" => $i[3],
				"populationFemale" => $i[4],
			]);
		}, $array);

		$file->delete();

		return $array;
	}

	public function getNUTS3() : ?NUTS3
	{
		if ($this->getData()["nuts3"] ?? null) {
			return new NUTS3($this->getData()["nuts3"]);
		}

		try {
			$claims = $this->getWikidataArticleJSON()->getArray()["claims"]["P131"];
			usort($claims, function ($a, $b) {
				$aDatetime = new \App\Classes\Time($a["qualifiers"]["P580"][0]["datavalue"]["value"]["time"] ?? null);
				$bDatetime = new \App\Classes\Time($b["qualifiers"]["P580"][0]["datavalue"]["value"]["time"] ?? null);

				return $aDatetime > $bDatetime ? -1 : 1;
			});

			foreach ($claims as $claim) {
				$articleTitle = $claim["mainsnak"]["datavalue"]["value"]["id"];
				$articleJSON = \Heimat\Sources\Wikidata\Article::getJSON($articleTitle);
				foreach ($articleJSON->getArray()["claims"]["P31"] as $claim) {
					if ($claim["mainsnak"]["datavalue"]["value"]["id"] == NUTS3::getWikidataClass()) {
						$nuts3 = $articleJSON->getArray()["claims"]["P605"][0]["mainsnak"]["datavalue"]["value"];

						return new NUTS3($nuts3);
					}
				}
			}

			throw new \Exception;
		} catch (\Throwable $e) {
			try {
				$nuts3 = $this->getWikidataArticleJSON()->getArray()["claims"]["P605"][0]["mainsnak"]["datavalue"]["value"];

				return new NUTS3($nuts3);
			} catch (\Throwable $e) {
				return null;
			}
		}
	}

	public static function getWikidataClass() : string
	{
		return "Q548611";
	}
}
