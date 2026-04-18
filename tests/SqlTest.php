<?php declare(strict_types=1);

namespace OpenCore\Tests;

use OpenCore\Orm\Sql;
use PHPUnit\Framework\TestCase;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;

final class SqlTest extends TestCase {
  public function testNoAliases() {
    $tab1 = new SqlTable('tab1');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab1Prop = new SqlField($tab1, 'prop1');

    $res = Sql::select()
      ->fields([$tab1Id, $tab1Prop])
      ->whereEquals($tab1Id, 1)
      ->whereEquals($tab1Prop, 2)
      ->build();

    $this->assertEquals(
      'SELECT `id` AS `myId1`, `prop1`'
      . ' FROM `tab1`'
      . ' WHERE `id` = ? AND `prop1` = ?',
      $res->sql,
    );
    $this->assertEquals([1, 2], $res->params);
  }

  public function testCount() {
    $tab1 = new SqlTable('tab1');
    $tab2 = new SqlTable('tab2');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');

    $tab1Prop = new SqlField($tab1, 'prop1');
    $tab2Prop = new SqlField($tab2, 'prop2');

    $res = Sql::select()
      ->fields([$tab1Prop])
      ->selectCall(Sql::count($tab1Id), 'total')
      ->whereEquals($tab2Prop, 42)
      ->groupBy($tab1Prop)
      ->build();

    $this->assertEquals(
      'SELECT `t1`.`prop1`, COUNT(`t1`.`id`) AS `total`'
      . ' FROM `tab1` AS `t1`'
      . ' NATURAL INNER JOIN `tab2` AS `t2`'
      . ' WHERE `t2`.`prop2` = ?'
      . ' GROUP BY `t1`.`prop1`',
      $res->sql,
    );
    $this->assertEquals([42], $res->params);
  }

  public function testImplicitSelect() {
    $tab1 = new SqlTable('tab1');
    $tab2 = new SqlTable('tab2');
    $tab3 = new SqlTable('tab3');
    $tab4 = new SqlTable('tab4');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab2Id = new SqlField($tab2, 'id', 'myId2');
    $tab3Id = new SqlField($tab3, 'id', 'myId3');
    $tab4Id = new SqlField($tab4, 'id', 'myId4');

    $tab1Prop = new SqlField($tab1, 'prop1');
    $tab2Prop = new SqlField($tab2, 'prop2');
    $tab3Prop = new SqlField($tab3, 'prop3');

    $res = Sql::select()
      ->fields([$tab1Id, $tab1Prop])
      ->calcFoundRows()
      ->orderBy($tab2Id, reverse: true)
      ->orderBy($tab2Prop)
      ->whereEquals($tab3Id, 1)
      ->whereEquals($tab3Prop, 2)
      ->limit(14, 58)
      ->groupBy($tab3Id)
      ->groupBy($tab4Id)
      ->build();

    $this->assertEquals(
      'SELECT SQL_CALC_FOUND_ROWS'
      . ' `t1`.`id` AS `myId1`, `t1`.`prop1`'
      . ' FROM `tab1` AS `t1`'
      . ' NATURAL INNER JOIN `tab2` AS `t2`'
      . ' NATURAL INNER JOIN `tab4` AS `t3`'
      . ' NATURAL INNER JOIN `tab3` AS `t4`'
      . ' WHERE `t4`.`id` = ? AND `t4`.`prop3` = ?'
      . ' GROUP BY `t4`.`id`, `t3`.`id`'
      . ' ORDER BY `t2`.`id` DESC, `t2`.`prop2` ASC'
      . ' LIMIT 14 OFFSET 58',
      $res->sql,
    );
    $this->assertEquals([1, 2], $res->params);
  }

  public function testExplicitSelect() {
    $table = new SqlTable('tab');
    $tableExt1 = new SqlTable('extension1');
    $tableExt2 = new SqlTable('extension2');

    $idField = new SqlField($table, 'id', 'myId');
    $updField = new SqlField($table, 'updated', 'myUpdated');
    $cacheField = new SqlField($table, 'cache', 'myCache');
    $viewsField = new SqlField($table, 'views');
    $descrField = new SqlField($table, 'descr');
    $textField = new SqlField($table, 'text');

    $ext1Id = new SqlField($tableExt1, 'id');
    $ext1TabId = new SqlField($tableExt1, 'tab_id');
    $ext2ExtId = new SqlField($tableExt2, 'ext_id');

    $res = Sql::select()
      ->from($table)
      ->fields([$idField, $updField])
      ->join(Sql::innerJoin($tableExt1)->whereEquals($ext1TabId, $idField))
      ->join(Sql::innerJoin($tableExt2)->whereEquals($ext2ExtId, $ext1Id))
      ->fields([$ext1TabId, $ext2ExtId])
      ->calcFoundRows()
      ->orderBy($idField, reverse: true)
      ->orderBy($ext2ExtId)
      ->whereEquals($idField, [1, 2, 3])
      ->whereEquals($updField, 1024)
      ->whereEquals($viewsField, null)
      ->where(Sql::expr()->notEquals($descrField, null))
      ->where(Sql::expr()->like($descrField, 'hello%_world'))
      ->where(
        Sql::or()
          ->like($textField, 'like1')
          ->like($textField, 'LiKe2', caseInsensitive: true)
          ->notEquals($textField, 'ne1')
          ->expr(
            Sql::and()
              ->equals($textField, 'val1')
              ->notEquals($descrField, 'val2')
              ->expr(Sql::or()->equals($textField, 'val3'))
              ->expr(Sql::and()->equals($textField, 'val4')),
          ),
      )
      ->build();

    $this->assertEquals(
      'SELECT SQL_CALC_FOUND_ROWS'
      . ' `t1`.`id` AS `myId`, `t1`.`updated` AS `myUpdated`, `e2`.`tab_id`, `e3`.`ext_id`'
      . ' FROM `tab` AS `t1`'
      . ' INNER JOIN `extension1` AS `e2` ON `e2`.`tab_id` = `t1`.`id`'
      . ' INNER JOIN `extension2` AS `e3` ON `e3`.`ext_id` = `e2`.`id`'
      . ' WHERE `t1`.`id` IN (?, ?, ?) AND `t1`.`updated` = ? AND `t1`.`views` IS NULL'
        .' AND `t1`.`descr` IS NOT NULL AND `t1`.`descr` LIKE ?'
        . ' AND ('
          .'`t1`.`text` LIKE ?'
          .' OR LOWER(`t1`.`text`) LIKE ?'
          .' OR `t1`.`text` != ?'
          .' OR'
            .' `t1`.`text` = ?'
            .' AND `t1`.`descr` != ?'
            .' AND `t1`.`text` = ?'
            .' AND `t1`.`text` = ?'
        .')'
      . ' ORDER BY `t1`.`id` DESC, `e3`.`ext_id` ASC',
      $res->sql,
    );
    $this->assertEquals([
      1, 2, 3, 1024, '%hello\%\_world%', '%like1%', '%like2%',
      'ne1', 'val1', 'val2', 'val3', 'val4',
    ], $res->params);
  }

  public function testDelete() {
    $tab1 = new SqlTable('tab1');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab1Prop = new SqlField($tab1, 'prop1');

    $res = Sql::delete($tab1)
      ->whereEquals($tab1Id, $tab1Prop)
      ->whereEquals($tab1Id, 1)
      ->build();

    $this->assertEquals('DELETE FROM `tab1` WHERE `id` = `prop1` AND `id` = ?', $res->sql);
    $this->assertEquals([1], $res->params);
  }

  public function testInsert() {
    $tab1 = new SqlTable('tab1');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab1Prop = new SqlField($tab1, 'prop1');

    $res = Sql::insert($tab1)
      ->fields([$tab1Id, $tab1Prop])
      ->values([1, 2])
      ->build();

    $this->assertEquals('INSERT INTO `tab1` (`id`, `prop1`) VALUES (?, ?)', $res->sql);
    $this->assertEquals([1, 2], $res->params);
  }

  public function testInsertMultiple() {
    $tab1 = new SqlTable('tab1');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab1Prop = new SqlField($tab1, 'prop1');

    $res = Sql::insert($tab1)
      ->fields([$tab1Id, $tab1Prop])
      ->values([1, 2])
      ->values([3, 4])
      ->build();

    $this->assertEquals('INSERT INTO `tab1` (`id`, `prop1`) VALUES (?, ?), (?, ?)', $res->sql);
    $this->assertEquals([1, 2, 3, 4], $res->params);
  }

  public function testUpdate() {
    $tab1 = new SqlTable('tab1');

    $tab1Id = new SqlField($tab1, 'id', 'myId1');
    $tab1Prop = new SqlField($tab1, 'prop1');

    $res = Sql::update($tab1)
      ->set($tab1Id, 1)
      ->set($tab1Prop, 2)
      ->whereEquals($tab1Id, 11)
      ->whereEquals($tab1Prop, 22)
      ->build();

    $this->assertEquals('UPDATE `tab1` SET `id` = ?, `prop1` = ? WHERE `id` = ? AND `prop1` = ?', $res->sql);
    $this->assertEquals([1, 2, 11, 22], $res->params);
  }
}
