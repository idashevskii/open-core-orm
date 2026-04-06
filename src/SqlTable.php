<?php declare(strict_types=1);

namespace OpenCore\Orm;

final class SqlTable {
  private static int $globalIds = 0;
  public readonly int $id;

  public function __construct(
    public readonly string $name,
  ) {
    $this->id = ++self::$globalIds;
  }
}
