<?php declare(strict_types=1);

namespace OpenCore\Tests\Models;

use OpenCore\Orm\Annotations\{Table, Field};

#[Table('uc_group')]
final class Group {
  #[Field(primaryKey: true)]
  public ?int $id;

  #[Field]
  public ?string $name;

  #[Field('owner_id', foreignKey: User::class)]
  public ?int $ownerId;
}
