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
}
