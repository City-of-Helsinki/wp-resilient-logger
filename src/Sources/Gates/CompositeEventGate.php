<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Gates;

final class CompositeEventGate implements EventGate
{
	private array $gates;

	public function __construct(
		EventGate ...$gates
	) {
		$this->gates = $gates;
	}

	public function should_log( int $event_id, array $data ): bool
	{
		foreach ( $this->gates as $gate ) {
			if ( ! $gate->should_log( $event_id, $data ) ) {
				return false;
			}
		}

		return true;
	}
}
