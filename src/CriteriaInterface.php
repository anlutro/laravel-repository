<?php
namespace anlutro\LaravelRepository;

interface CriteriaInterface
{
	public function apply($query);
}
