<?php

namespace Heimat\Schema;

class LAU2 extends \Heimat\SchemaObject
{
	public static function getList(?int $year = null) : array
	{
		$url = \Heimat\Sources\CZSO\Population::getTableUrlByTitle("Počet obyvatel v obcích České republiky", $year);
		if (!$url) {
			return null;
		}

		$file = \Katu\Files\File::createTemporaryFromURL($url, 'xlsx');

		$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$worksheet = $xls->getSheet(0);

		$array = $worksheet->toArray();
		$array = array_values(array_filter($array, function ($i) {
			return preg_match('/^CZ[0-9A-Z]{4}$/', $i[0]);
		}));
		$array = array_map(function ($i) {
			return new static($i[1], $i[2], [
				'lau1' => $i[0],
				'population' => $i[3],
				'populationMale' => $i[4],
				'populationFemale' => $i[5],
			]);
		}, $array);

		$file->delete();

		return $array;
	}

	public function getWikidataReference() : string
	{
		return 'CZ' . $this->getReference();
	}

	public function getLAU1() : LAU1
	{
		$articleTitle = $this->getWikidataArticleJSON()->getArray()['claims']['P131'][0]['mainsnak']['datavalue']['value']['id'];
		$articleJSON = \Heimat\Sources\Wikidata\Article::getJSON($articleTitle);

		$lau1 = $articleJSON->getArray()['claims']['P782'][0]['mainsnak']['datavalue']['value'];

		return new LAU1($lau1);
	}
}
