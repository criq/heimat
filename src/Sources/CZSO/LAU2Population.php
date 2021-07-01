<?php

namespace Heimat\Sources\CZSO;

class LAU2Population
{
	public static function getData(?int $year = null) : array
	{
		$url = Population::getTableUrlByTitle("Počet obyvatel v obcích České republiky", $year);
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
			return [
				'lau1' => $i[0],
				'lau2' => $i[1],
				'name' => $i[2],
				'population' => $i[3],
				'populationMale' => $i[4],
				'populationFemale' => $i[5],
			];
		}, $array);

		$file->delete();

		return $array;
	}
}
