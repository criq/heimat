<?php

namespace Heimat\Sources\CZSO;

class NUTS3Population
{
	public static function getData(?int $year = null) : array
	{
		$url = Population::getTableUrlByTitle("Počet obyvatel v regionech soudržnosti, krajích a okresech České republiky", $year);
		if (!$url) {
			return null;
		}

		$file = \Katu\Files\File::createTemporaryFromURL($url, 'xlsx');

		$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$worksheet = $xls->getSheet(0);

		$array = $worksheet->toArray();
		$array = array_values(array_filter($array, function ($i) {
			return preg_match('/^CZ[0-9]{3}$/', $i[0]);
		}));
		$array = array_map(function ($i) {
			return [
				'nuts3' => $i[0],
				'name' => $i[1],
				'population' => $i[2],
				'populationMale' => $i[3],
				'populationFemale' => $i[4],
			];
		}, $array);

		$file->delete();

		return $array;
	}
}
