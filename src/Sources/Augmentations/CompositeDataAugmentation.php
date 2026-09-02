<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Augmentations;

final class CompositeDataAugmentation implements DataAugmentation
{
	private array $augmentations;

	public function __construct(
		DataAugmentation ...$augmentations
	) {
		$this->augmentations = $augmentations;
	}

	public function augment( array &$data ): void
	{
		foreach ( $this->augmentations as $augmentation ) {
			$augmentation->augment( $data );
		}
	}
}
