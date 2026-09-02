<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Gates;

interface EventGate
{
	public function should_log( int $event_id, array $data ): bool;
}
