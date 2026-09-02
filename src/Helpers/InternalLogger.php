<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Helpers;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 compliant logger to be used for internal messages
 */
class InternalLogger implements LoggerInterface
{
	public function log($level, $message, array $context = []): void
	{
		$level = strtoupper((string) $level);
		$formatted = $this->format_message( $level, (string) $message, $context );

		if ( $this->should_error_log( $level ) ) {
			error_log( $formatted );
		}

		if ( \apply_filters( 'helsinki_wp_resilient_logger_is_cli', false ) ) {
			\WP_CLI::log( sprintf(
				"%s\n",
				\WP_CLI::colorize( $this->parse_color( $level ) . $formatted . '%n' )
			) );
		}
	}

	private function should_error_log( string $level ): bool
	{
		if ( \apply_filters( 'helsinki_wp_resilient_logger_is_debug', false ) ) {
			return true;
		}

		return in_array( $level, array(
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR
		), true );
	}

	private function format_message( string $level, string $message, array $context ): string
	{
		$message = $this->interpolate((string) $message, $context);

		if ( ! empty( $context ) ) {
			// Remove items already used as placeholders to avoid redundancy
			$remaining = array_filter(
				$context,
				fn($key) => strpos($message, '{' . $key . '}') === false,
				ARRAY_FILTER_USE_KEY
			);

			if ( ! empty( $remaining ) ) {
				$message .= ' | Context: ' . json_encode( $remaining, JSON_UNESCAPED_UNICODE );
			}
		}

		return sprintf( '[wp-resilient-logger] [%s] %s', $level, $message );
	}

	private function interpolate(string $message, array $context): string
	{
		$replace = array();

		foreach ( $context as $key => $val ) {
			// Only scalars and stringable objects can be placeholders
			if ( is_scalar($val) || (is_object($val) && method_exists($val, '__toString') ) ) {
				$replace['{' . $key . '}'] = $val;
			}
		}

		return strtr( $message, $replace );
	}

	/**
	  * Map PSR-3 levels to WP-CLI color tokens.
	  */
	private function parse_color(string $level): string
	{
		return match ($level) {
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR   => '%R', // Bright Red
			LogLevel::WARNING => '%Y', // Yellow
			LogLevel::NOTICE  => '%G', // Green
			LogLevel::INFO    => '%B', // Blue
			LogLevel::DEBUG   => '%c', // Cyan
			default           => '%n', // Reset/Default
		};
	}

	public function emergency($message, array $context = []): void
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}

	public function alert($message, array $context = []): void
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical($message, array $context = []): void
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error($message, array $context = []): void
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning($message, array $context = []): void
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice($message, array $context = []): void
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info($message, array $context = []): void
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug($message, array $context = []): void
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}
}
