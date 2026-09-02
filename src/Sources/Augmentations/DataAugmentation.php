<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Augmentations;

interface DataAugmentation
{
	public function augment( array &$data ): void;
}
