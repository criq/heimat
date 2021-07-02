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
}
