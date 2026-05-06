<?php

namespace CityOfHelsinki\WP\ResilientLogger\Sources\WSAL\Lookups;

final class CrudOperations
{
	const CREATE = 'CREATE';
	const DELETE = 'DELETE';
	const UPDATE = 'UPDATE';
	const READ = 'READ';

	private array $type_op;
	private array $legacy_op;

	public function of_type( string $type ): string
	{
		if ( ! isset( $this->type_op ) ) {
			$this->type_op = array(
				'activated' => self::CREATE,
				'added' => self::CREATE,
				'approved' => self::CREATE,
				'created' => self::CREATE,
				'executed' => self::CREATE,
				'exported' => self::CREATE,
				'imported' => self::CREATE,
				'installed' => self::CREATE,
				'login' => self::CREATE,
				'published' => self::CREATE,
				'sent' => self::CREATE,
				'submitted' => self::CREATE,
				'uploaded' => self::CREATE,

				'blocked' => self::DELETE,
				'deactivated' => self::DELETE,
				'deleted' => self::DELETE,
				'denied' => self::DELETE,
				'failed' => self::DELETE,
				'failed-login' => self::DELETE,
				'logout' => self::DELETE,
				'unapproved' => self::DELETE,
				'uninstalled' => self::DELETE,
				'revoked' => self::DELETE,

				'disabled' => self::UPDATE,
				'duplicated' => self::UPDATE,
				'enabled' => self::UPDATE,
				'modified' => self::UPDATE,
				'renamed' => self::UPDATE,
				'restored' => self::UPDATE,
				'starred' => self::UPDATE,
				'updated' => self::UPDATE,

				'available' => self::READ,
				'read' => self::READ,
				'opened' => self::READ,
				'viewed' => self::READ,
			);
		}

		return $this->type_op[$type] ?? '';
	}

	public function of_code( int $code ): string
	{
		if ( ! isset( $this->legacy_op ) ) {
			$this->legacy_op = array(
				2034 => self::DELETE,
				2035 => self::DELETE,
				2009 => self::DELETE,
				2013 => self::DELETE,
				2015 => self::DELETE,
			);
		}

		return $this->legacy_op[$code] ?? '';
	}

	public function default_operation(): callable
	{
		return fn( int $code ) => ($code >= 1000 && $code <= 1999) ? 'READ' : 'UPDATE';
	}
}
