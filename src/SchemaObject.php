<?php

namespace Heimat;

class SchemaObject
{
	public function __construct(string $id, ?string $name = null, ?array $data = null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->data = $data;
	}
}
