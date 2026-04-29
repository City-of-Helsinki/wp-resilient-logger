<?php

declare(strict_types=1);

namespace WP\helfi_resilient_logger\Helpers;

use WSAL\Controllers\Alert_Manager;

class WSALLookup {
  /**
   * Map of WSAL metadata keys to their respective resource types.
   */
  private const TARGET_LOOKUP = [
      // 1. Primary Entity Anchors (Highest Priority)
      'PostID'          => 'post',
      'ID'              => 'post',            // Found in Tags/Categories metadata
      'SessionID'       => 'user_session',
      'TargetSessionID' => 'user_session',
      'TermID'          => 'taxonomy_term',   
      'UserID'          => 'user',

      // 2. Structural Anchors (Required for Taxonomy events)
      'Slug'            => 'taxonomy_term',   // Critical: Used in 2121, 2123, 2052, etc.
      'CategoryName'    => 'taxonomy_term',
      'TagName'         => 'taxonomy_term',

      // 3. Asset Anchors
      'FileName'        => 'file',            // Found in 2010
      'FilePath'        => 'file',            // Found in 2010 (as Directory)

      // 4. Descriptor Fallbacks (Lowest Priority)
      'PostType'        => 'post',
      'PostStatus'      => 'post',
      'TargetUserRole'  => 'user',
      'ClientIP'        => 'user',
      'IPAddress'       => 'user',
  ];

  private static ?array $wsalLookup = null;

  /**
   * @return array{op: string; desc: string;}
   */
  private static function getAlertLookup(): array {
    if (is_null(self::$wsalLookup)) {
      $lookup = [];
      $alerts = Alert_Manager::get_alerts();

      foreach ($alerts as $code => $data) {
        // Extract a clean description
        $desc = $data['desc'] ?? "Event {$code}";
        $type = $data['event_type'] ?? '';

        // Determine CRUD Operation
        $op = match (true) {
          $type === 'created' || $type === 'published' || $type === 'uploaded' => 'CREATE',
          $type === 'deleted' || str_contains($desc, 'trash')                  => 'DELETE',
          $type === 'viewed'  || $type === 'opened'    || $type === 'login'    => 'READ',
          default => 'UPDATE'
        };

        $lookup[$code] = ['op' => $op, 'desc' => $desc];
      }

      self::$wsalLookup = $lookup;
    }

    return self::$wsalLookup;
  }

  /**
   * @param array<string, string> $meta
   * @param array<string, mixed> $row
   * @return array{id: string|int, type: string, site_id: int|string}
   */
  public static function parseAlertTarget(array $meta, array $row): array {
      $targetId   = 'unknown';
      $targetType = 'unmapped_target';

      // The order of WSAL_TARGET_LOOKUP keys determines priority
      foreach (self::TARGET_LOOKUP as $metaKey => $typeHint) {
          if (isset($meta[$metaKey])) {
              $targetId   = $meta[$metaKey];
              $targetType = $typeHint;
              break;
          }
      }

      if ($targetId === 'unknown') {
          $targetId = 'alert_' . ($row['alert_id'] ?? 'none');
      }

      return [
          'id'      => $targetId,
          'type'    => $targetType,
          'site_id' => $row['site_id'] ?? '1',
      ];
  }

  /**
   * @param array<string, mixed> $row
   * @return array{operation: string, description: string}
   */
  public static function parseAlertDetails(array $row): array {
    $id = (int) $row['alert_id'];
    $lookup = self::getAlertLookup();

    // Check our lean lookup first
    if (isset($lookup[$id])) {
        return [
            'operation'   => $lookup[$id]['op'],
            'description' => $lookup[$id]['desc']
        ];
    }

    // Fallback logic for IDs not present in the bootstrapper (System safety)
    $fallbackOp = ($id >= 1000 && $id <= 1999) ? 'READ' : 'UPDATE';
    
    return [
        'operation'   => $fallbackOp,
        'description' => "Activity Log Event ({$id})"
    ];
}
}
