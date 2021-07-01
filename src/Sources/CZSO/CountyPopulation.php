<?php

namespace Heimat\Sources\CZSO;

class CountyPopulation
{
	public static function getData(int $year) : array
	{
		$url = "https://www.czso.cz/csu/czso/pocet-obyvatel-v-obcich-k-11" . $year;
		$src = \Katu\Cache\URL::get($url, '1 day');
		$dom = \Katu\Tools\DOM\DOM::crawlHtml($src);

		$array = $dom->filter('.prilohy-publikace tr')->each(function ($e) {
			if (preg_match('/Počet obyvatel v regionech soudržnosti, krajích a okresech České republiky/', $e->filter('td')->eq(0)->text())) {
				return $e->filter('td')->eq(1)->filter('a')->each(function ($e) {
					if (preg_match('/Excel/', $e->text())) {
						return $e->attr('href');
					}
				});
			}
		});

		$url = (new TArray($array))->flatten()->filter()->values()[0];
		$file = \Katu\Files\File::createTemporaryFromURL($url, 'xlsx');

		$xls = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
		$worksheet = $xls->getSheet(0);

		$array = $worksheet->toArray();
		var_dump($array);die;
		$array = array_values(array_filter($array, function ($i) {
			return preg_match('/CZ[0-9A-Z]{4}/', $i[0]);
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

		return $array;
	}
}
