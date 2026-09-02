<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Sources\Augmentations;

final class AddRequestId implements DataAugmentation
{
	private array $headers;

	public function augment( array &$data ): void
	{
		$data['RequestID'] = $this->header_value( 'X-Request-ID', 'N/A' );
	}

	private function header_value( string $name, string $default ): string
	{
		if ( isset( $this->headers[$name] ) ) {
			return $this->headers[$name];
		}

		if ( ! isset( $this->headers ) ) {
			$this->headers = array();
		}

		// Normalize the name to the PHP $_SERVER format
        // Example: 'X-Request-ID' becomes 'HTTP_X_REQUEST_ID'
		$header = 'HTTP_' . strtoupper( str_replace( '-', '_', $name ) );

		$this->headers[$name] = ! empty( $_SERVER[$header] )
			? \sanitize_text_field( $_SERVER[$header] )
			: $default;

		return $this->headers[$name];
	}
}
