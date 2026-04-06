<?php declare(strict_types=1);

namespace OpenCore\Orm\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field {
  public function __construct(
    public readonly ?string $name = null,
    public readonly bool $primaryKey = false,
    public readonly ?string $foreignKey = null,
    public readonly ?string $externalTable = null,
  ) {}
}
