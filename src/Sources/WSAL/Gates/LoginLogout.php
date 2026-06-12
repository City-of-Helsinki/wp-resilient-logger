<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Gates;

use CityOfHelsinki\WP\ResilientLogger\Sources\Gates\EventGate;

final class LoginLogout implements EventGate
{
	public function should_log( int $event_id, array $data ): bool
	{
		return match( $event_id ) {
			1000 => $this->log_action( 'login', $data ),
			1001 => $this->log_action( 'logout', $data ),
			default => true,
		};
	}

	private function log_action( string $action, array $data ): bool
	{
		if ( ! $this->determine_logging( $action ) ) {
			return false;
		}

		foreach( $this->user_roles( $data ) as $role ) {
			if ( ! $this->determine_logging( $role, $action ) ) {
				return false;
			}
		}

		return true;
	}

	private function determine_logging( string ...$parts ): bool
	{
		return \apply_filters(
			sprintf( 'helsinki_wp_resilient_logger_log_%s', implode( '_', $parts ) ),
			true
		);
	}

	private function user_roles( array $data ): array
	{
		if (
			isset( $data['CurrentUserRoles'] )
			&& is_array( $data['CurrentUserRoles'] )
		) {
			return $data['CurrentUserRoles'];
		}

		return array();
	}
}
