<?php declare(strict_types=1);

namespace OpenCore\Tests;

use OpenCore\Tests\Models\Group;
use OpenCore\Tests\Models\User;
use OpenCore\Orm\ObjectDefinition;
use OpenCore\Tests\Models\UserExtended;
use PHPUnit\Framework\TestCase;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;

final class ObjectDefinitionTest extends TestCase {
  public function testPrimaryKey() {
    $def = ObjectDefinition::from(User::class);

    $idProp = $def->getSqlField('id');
    $idPropPk = $def->getSqlPrimary();

    $this->assertInstanceOf(SqlField::class, $idProp);
    $this->assertEquals($idProp, $idPropPk);
  }

  public function testForeignKey() {
    $def = ObjectDefinition::from(User::class);

    $foreClass = $def->getForeignClass('groupId');
    $foreProp = $def->getForeignProp(Group::class);

    $this->assertEquals(Group::class, $foreClass);
    $this->assertEquals('groupId', $foreProp);
  }

  public function testTable() {
    $def = ObjectDefinition::from(User::class);

    $table = $def->getSqlTable();

    $this->assertEquals($table->name, 'uc_user');
    $this->assertInstanceOf(SqlTable::class, $table);
  }

  public function testComposition() {
    $def = ObjectDefinition::from(UserExtended::class);

    $res = [];

    $tableOrder = [];
    foreach ($def->getRequiredDefinitions() as $otherDef) {
      $props = $otherDef->getOwnProperties();
      $tabName = $otherDef->getSqlTable()->name;
      sort($props);
      $res[$tabName] = $props;
      $tableOrder[] = $tabName;
    }

    $this->assertEquals([
      'uc_user_base',
      'uc_user',
      'uc_user_extended',
    ], $tableOrder);

    $this->assertEquals([
      'uc_user_base' => ['id', 'lastLoginTime', 'login', 'passwordHash'],
      'uc_user' => ['groupId', 'id', 'nickname', 'signupTime'],
      'uc_user_extended' => ['email', 'id', 'phoneNumber'],
    ], $res);
  }
}
