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

	public function getLAU1() : LAU1
	{
		try {
			$array = $this->getWikidataArticleJSON()->getArray()['claims']['P131'];
			usort($array, function ($a, $b) {
				$aDatetime = new \App\Classes\DateTime($a['qualifiers']['P580'][0]['datavalue']['value']['time']);
				$bDatetime = new \App\Classes\DateTime($b['qualifiers']['P580'][0]['datavalue']['value']['time']);

				return $aDatetime > $bDatetime ? -1 : 1;
			});

			$articleTitle = $array[0]['mainsnak']['datavalue']['value']['id'];
			$articleJSON = \Heimat\Sources\Wikidata\Article::getJSON($articleTitle);

			$lau1 = $articleJSON->getArray()['claims']['P782'][0]['mainsnak']['datavalue']['value'];

			return new LAU1($lau1);
		} catch (\Throwable $e) {
			foreach ($this->getWikidataArticleJSON()->getArray()['claims']['P782'] as $claim) {
				if (($claim['qualifiers']['P3831'][0]['datavalue']['value']['id'] ?? null) == 'Q11618279') {
					$lau1 = $claim['mainsnak']['datavalue']['value'];

					return new LAU1($lau1);
				}
			}
		}
	}

	public function getWikidataClass() : string
	{
		return 'Q5153359';
	}

	public function getWikidataReference() : string
	{
		return 'CZ' . $this->getReference();
	}
}
