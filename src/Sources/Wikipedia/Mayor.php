<?php

namespace Heimat\Sources\Wikipedia;

class Mayor
{
	protected $role;
	protected $name;

	public function __construct(string $role, string $name)
	{
		$this->role = $role;
		$this->name = $name;
	}

	public function getRole() : string
	{
		return $this->role;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getGender() : ?string
	{
		$map = [
			'starosta' => 'male',
			'starostka' => 'female',
			'primátor' => 'male',
			'primátorka' => 'female',
		];

		return $map[$this->getRole()] ?? null;
	}

	public static function getNameFromSource($value)
	{
		$res = $value;
		$res = preg_replace('/<ref.+/', '', $res);
		$res = preg_replace('/&nbsp;/', ' ', $res);
		$res = preg_replace('/\{\{nowrap\|(.+)\}\}/U', '\\1', $res);
		$res = preg_replace('/<!--.*-->/U', '', $res);
		$res = preg_replace('/\s*\(.+\)\s*/U', '', $res);
		$res = preg_replace('/\[\[(.+)\|(.+)\]\]/U', '\\2', $res);
		$res = preg_replace('/\[\[(.+)\]\]/U', '\\1', $res);
		$res = preg_replace('/BEZPP/', '', $res);
		$res = trim($res);

		return $res;
	}
}
