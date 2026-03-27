<?php declare(strict_types=1);

namespace OpenCore\Tests\Models;

use OpenCore\Orm\Annotations\{Table, Field};

#[Table('uc_user_base')]
class UserBase {
  #[Field(primaryKey: true)]
  public ?int $id;

  #[Field(externalTable: self::class)]
  public ?string $login;

  #[Field('password_hash', externalTable: self::class)]
  public ?string $passwordHash;

  #[Field('last_login_time', externalTable: self::class)]
  public ?int $lastLoginTime;
}
