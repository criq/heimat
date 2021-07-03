<?php

namespace Heimat\Schema;

use Katu\Types\TArray;
use Katu\Types\TString;

class LAU2 extends \Heimat\SchemaObject
{
	public static function getList(?int $year = null) : array
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__, $year], '1 week', function ($year) {
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
		}, $year);
	}

	public function getLAU1() : LAU1
	{
		try {
			$claims = $this->getWikidataArticleJSON()->getArray()['claims']['P131'];
			usort($claims, function ($a, $b) {
				$aDatetime = new \App\Classes\DateTime($a['qualifiers']['P580'][0]['datavalue']['value']['time'] ?? null);
				$bDatetime = new \App\Classes\DateTime($b['qualifiers']['P580'][0]['datavalue']['value']['time'] ?? null);

				return $aDatetime > $bDatetime ? -1 : 1;
			});

			foreach ($claims as $claim) {
				$articleTitle = $claim['mainsnak']['datavalue']['value']['id'];
				$articleJSON = \Heimat\Sources\Wikidata\Article::getJSON($articleTitle);

				foreach ($articleJSON->getArray()['claims']['P31'] as $claim) {
					if ($claim['mainsnak']['datavalue']['value']['id'] == LAU1::getWikidataClass()) {
						$lau1 = $articleJSON->getArray()['claims']['P782'][0]['mainsnak']['datavalue']['value'];

						return new LAU1($lau1);
					}
				}
			}

			throw new \Exception;
		} catch (\Throwable $e) {
			foreach ($this->getWikidataArticleJSON()->getArray()['claims']['P782'] as $claim) {
				if (($claim['qualifiers']['P3831'][0]['datavalue']['value']['id'] ?? null) == 'Q11618279') {
					$lau1 = $claim['mainsnak']['datavalue']['value'];

					return new LAU1($lau1);
				}
			}
		}
	}

	public static function getWikidataClass() : string
	{
		return 'Q5153359';
	}

	public function getWikidataReference() : string
	{
		return 'CZ' . $this->getReference();
	}

	public static function getStandardPostalCode(string $value) : int
	{
		return (int)preg_replace('/\s/', '', $value);
	}

	public static function getFormattedPostalCode(string $value) : string
	{
		$value = static::getStandardPostalCode($value);

		return implode(' ', [
			substr($value, 0, 3),
			substr($value, 3, 2),
		]);
	}

	public function getWikidataPostalCodes() : array
	{
		return \Katu\Cache\General::get([__CLASS__, __FUNCTION__, $this->getReference()], '1 week', function () {
			$array = [];
			foreach (($this->getWikidataArticleJSON()->getArray()['claims']['P281'] ?? []) as $claim) {
				$string = (string)(new TString($claim['mainsnak']['datavalue']['value']))->normalizeSpaces();
				$string = preg_replace('/&nbsp;/', '', $string);
				$array[] = $string;
			}

			$postalCodeRegexp = '[0-9]\s*[0-9]\s*[0-9]\s*[0-9]\s*[0-9]';
			$postalCodes = new TArray;
			foreach ($array as $item) {
				if (preg_match("/^$postalCodeRegexp$/", $item)) {
					$postalCodes->append($item);
				} elseif (preg_match("/^(?<start>$postalCodeRegexp)" . "(–|\s*až\s*)" . "(?<end>$postalCodeRegexp)$/", $item, $match)) {
					$postalCodes->append(range(static::getStandardPostalCode($match['start']), static::getStandardPostalCode($match['end'])));
				} else {
					var_dump('---------------------------------------------------------');die;
					var_dump($item);die;
				}
			}

			return $postalCodes->flatten()->map(function ($i) {
				return static::getFormattedPostalCode($i);
			})->unique()->values()->getArray();
		});
	}
}
