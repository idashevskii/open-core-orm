<?php declare(strict_types=1);

namespace OpenCore\Tests\Models;

use OpenCore\Orm\Annotations\{Table, Field};

#[Table('uc_user')]
class User extends UserBase {
  #[Field(primaryKey: true)]
  public ?int $id;

  #[Field(externalTable: self::class)]
  public ?string $nickname;

  #[Field('signup_time', externalTable: self::class)]
  public ?int $signupTime;

  #[Field('group_id', externalTable: self::class, foreignKey: Group::class)]
  public ?int $groupId;
}
