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
		if ($this->getData()['lau1'] ?? null) {
			return new LAU1($this->getData()['lau1']);
		}

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

	public static function getUniqueFormattedPostalCodes(array $value) : array
	{
		return (new TArray($value))->flatten()->map(function ($i) {
			return static::getFormattedPostalCode($i);
		})->unique()->natsort()->values()->getArray();
	}

	public static function extractPostalCodes(string $value) : array
	{
		$string = strip_tags($value);
		$string = (new TString($string))->normalizeSpaces();
		$string = preg_replace('/&nbsp;/', '', $string);

		$items = preg_split('/(,|\/|\sa\s)/', $string);
		$items = array_map('trim', $items);

		$postalCodeRegexp = '[0-9]\s*[0-9]\s*[0-9]\s*[0-9]\s*[0-9]';
		$postalCodes = new TArray;
		foreach ($items as $item) {
			if (preg_match("/^$postalCodeRegexp$/", $item)) {
				$postalCodes->append($item);
			} elseif (preg_match("/^(?<start>$postalCodeRegexp)\s*(–|-|až)\s*(?<end>$postalCodeRegexp)$/", $item, $match)) {
				$postalCodes->append(range(static::getStandardPostalCode($match['start']), static::getStandardPostalCode($match['end'])));
			} else {
				var_dump('------------------------------------------------------------------------------------------------------------------');
				var_dump($item);
				var_dump('------------------------------------------------------------------------------------------------------------------');
			}
		}

		return static::getUniqueFormattedPostalCodes($postalCodes->getArray());
	}

	public function getWikidataPostalCodes() : array
	{
		$postalCodes = new TArray;
		foreach (($this->getWikidataArticleJSON()->getArray()['claims']['P281'] ?? []) as $claim) {
			$postalCodes->append(static::extractPostalCodes($claim['mainsnak']['datavalue']['value']));
		}

		return static::getUniqueFormattedPostalCodes($postalCodes->getArray());
	}

	public function getWikipediaPostalCodes() : array
	{
		$postalCodes = new TArray;
		if (preg_match('/\|\s*PSČ\s*=\s*(?<postalCodes>.+)/', $this->getWikipediaArticleContents(), $match)) {
			$postalCodes->append(static::extractPostalCodes($match['postalCodes']));
		}

		return static::getUniqueFormattedPostalCodes($postalCodes->getArray());
	}

	public function getPostalCodes() : array
	{
		return static::getUniqueFormattedPostalCodes([
			$this->getWikidataPostalCodes(),
			$this->getWikipediaPostalCodes(),
		]);
	}
}
