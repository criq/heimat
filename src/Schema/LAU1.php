<?php

namespace Heimat\Schema;

class LAU1 extends \Heimat\SchemaObject
{
	public static function getList(?int $year = null) : array
	{
		$url = \Heimat\Sources\CZSO\Population::getTableUrlByTitle("Počet obyvatel v regionech soudržnosti, krajích a okresech České republiky", $year);
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
			return new static($i[0], $i[1], [
				'population' => $i[2],
				'populationMale' => $i[3],
				'populationFemale' => $i[4],
			]);
		}, $array);

		$file->delete();

		return $array;
	}

	public function getNUTS3() : NUTS3
	{
		$array = $this->getWikidataArticleJSON()->getArray()['claims']['P131'];
		usort($array, function ($a, $b) {
			$aDatetime = new \App\Classes\DateTime($a['qualifiers']['P580'][0]['datavalue']['value']['time']);
			$bDatetime = new \App\Classes\DateTime($b['qualifiers']['P580'][0]['datavalue']['value']['time']);

			return $aDatetime > $bDatetime ? -1 : 1;
		});

		$articleTitle = $array[0]['mainsnak']['datavalue']['value']['id'];
		$articleJSON = \Heimat\Sources\Wikidata\Article::getJSON($articleTitle);

		$nuts3 = $articleJSON->getArray()['claims']['P605'][0]['mainsnak']['datavalue']['value'];

		return new NUTS3($nuts3);
	}
}
