<?php

declare(strict_types=1);

namespace CityOfHelsinki\WP\ResilientLogger\Handlers;

use CityOfHelsinki\WP\ResilientLogger\Sources\ResilientLogSource;
use ResilientLogger\Handler\ResilientLogHandler as ResilientLogHandlerBase;

class ResilientLogHandler extends ResilientLogHandlerBase {
  /**
   * Same as base ResilientLogHandler but ResilientLogSource passed in by default.
   *
   * @param array<string> $requiredFields
   *   List of required context fields to be passed for the logger. 
   */
  public function __construct(array $requiredFields) {
    parent::__construct(ResilientLogSource::class, $requiredFields);
  }
}