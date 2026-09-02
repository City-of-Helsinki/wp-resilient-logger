<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Lookups;

use WSAL\Controllers\Alert_Manager;
use Closure;

final class AlertDetails
{
	private array $alerts;
	private Closure $default_op;

	public function parse( int $id ): array
	{
		if ( ! isset( $this->alerts ) ) {
			$this->prime_alerts();
		}

		if ( isset( $this->alerts[$id] ) ) {
			return array(
				'operation' => $this->alerts[$id]['op'],
				'description' => $this->alerts[$id]['desc'],
			);
		}

		return array(
			'operation' => ($this->default_op)( $id ),
			'description' => "Activity Log Event ({$id})",
		);
	}

	private function prime_alerts(): void
	{
		$this->alerts = array();

		$alerts = Alert_Manager::get_alerts();
		$operations = new CrudOperations();

		$this->default_op = Closure::fromCallable(
			$operations->default_operation()
		);

		foreach ( $alerts as $code => $data ) {
			$op = $operations->of_type( $data['event_type'] ?? '' )
				?: $operations->of_code( $code )
				?: ($this->default_op)( $code );

			$this->alerts[$code] = array(
				'op' => $op,
				'desc' => $data['desc'] ?? "Event {$code}",
			);
		}
	}
}
