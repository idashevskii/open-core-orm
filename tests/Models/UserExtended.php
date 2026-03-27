<?php declare(strict_types=1);

namespace OpenCore\Tests\Models;

use OpenCore\Orm\Annotations\{Table, Field};

#[Table('uc_user_extended')]
class UserExtended extends User {
  #[Field(primaryKey: true, foreignKey: User::class)]
  public ?int $id;

  #[Field]
  public ?string $email;

  #[Field('phone_number')]
  public ?string $phoneNumber;
}
