<?php

declare(strict_types=1);

namespace {
    class WpSecurityAuditLog {}
}

namespace WSAL\Controllers {
    class Alert_Manager {
        public static function get_alerts(): array { return []; }
    }
}

namespace WSAL\Entities {
  class Abstract_Entity {
		/**
		 * Returns the current connection (used by the plugin)
		 *
		 * @return \wpdb
		 *
		 * @since 4.4.2.1
		 */
		public static function get_connection() {}

		/**
		 * Returns the the table name
		 *
		 * @param \wpdb $connection -  - \wpdb connection to be used for name extraction.
		 *
		 * @return string
		 *
		 * @since 4.4.2.1
		 * @since 4.6.0 - Added $connection parameter
		 */
		public static function get_table_name( $connection = null ): string { return ''; }

    /**
		 * Builds a query based on the given parameters.
		 * The array could contain of:
		 *  - All ORs in the WHERE
		 *  - All ANDs in the WHERE
		 * It must be SQL string with interpolation, and its value.
		 *
		 * @param array $select_fields - Fields to use in the select statement of the query.
		 * @param array $query_parameters - Array with all the where clause expressions.
		 * @param array $order_by - Ordering array with fields and type of order.
		 * @param array $limit - Limit section of the query - array with range (if there is a need of range).
		 * @param array $join - Join section of the query.
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return array
		 *
		 * @since 4.6.0
		 */
		public static function build_query(
			array $select_fields = array(),
			array $query_parameters = array(),
			array $order_by = array(),
			array $limit = array(),
			array $join = array(),
			$connection = null
		) {}

		/**
		 * Load records from DB (Multi rows).
		 *
		 * @param string $cond Load condition.
		 * @param array  $args (Optional) Load condition arguments.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 * @param string $extra - The extra SQL string (if needed).
		 *
		 * @return array
		 *
		 * @since 5.0.0
		 */
		public static function load_array( $cond, $args = array(), $connection = null, $extra = '' ) {}
  }

  class Occurrences_Entity extends Abstract_Entity {
		/**
		 * Returns a key-value pair of metadata.
		 *
		 * @param array $events -  Array with all the events to extract meta data for.
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return array
		 *
		 * @since 4.6.0
		 */
		public static function get_multi_meta_array( array &$events, $connection = null ) {}
  }
}
