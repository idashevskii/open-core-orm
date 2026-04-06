<?php declare(strict_types=1);

namespace OpenCore\Orm;

final class SqlField {
  public readonly string $alias;

  public function __construct(
    public readonly SqlTable $table,
    public readonly string $name,
    ?string $alias = null,
  ) {
    $this->alias = $alias ?? $this->name;
  }
}
