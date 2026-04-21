<?php

declare(strict_types=1);

namespace OpenCore\Orm;

use OpenCore\Orm\Ast\SqlExprOpBinary;
use OpenCore\Orm\Ast\SqlJoinSpec;
use OpenCore\Orm\AstBuilder\SqlBinaryOp;
use OpenCore\Orm\AstBuilder\SqlCall;
use OpenCore\Orm\AstBuilder\SqlJoin;
use OpenCore\Orm\Statement\SqlDelete;
use OpenCore\Orm\Statement\SqlInsert;
use OpenCore\Orm\Statement\SqlSelect;
use OpenCore\Orm\Statement\SqlUpdate;

class Sql {
  public string $sql = '';
  public array $params = [];
  private $tableAliases = [];
  public bool $tableAliasesEnabled = false;

  public static function select(): SqlSelect {
    return new SqlSelect();
  }

  public static function delete(SqlTable $table): SqlDelete {
    return new SqlDelete($table);
  }

  public static function insert(SqlTable $table): SqlInsert {
    return new SqlInsert($table);
  }

  public static function update(SqlTable $table): SqlUpdate {
    return new SqlUpdate($table);
  }

  public static function count(SqlField $field): SqlCall {
    return self::call('COUNT')->withFieldArg($field);
  }

  public static function call(string $fn): SqlCall {
    return new SqlCall($fn);
  }

  public static function or(): SqlBinaryOp {
    return new SqlBinaryOp(SqlExprOpBinary::OP_OR);
  }

  public static function and(): SqlBinaryOp {
    return new SqlBinaryOp(SqlExprOpBinary::OP_AND);
  }

  public static function expr(): SqlBinaryOp {
    return self::and();
  }

  public static function naturalJoin(SqlTable $table): SqlJoin {
    return self::join(SqlJoinSpec::JOIN_NATURAL, $table);
  }

  public static function innerJoin(SqlTable $table): SqlJoin {
    return self::join(SqlJoinSpec::JOIN_INNER, $table);
  }

  public static function leftJoin(SqlTable $table): SqlJoin {
    return self::join(SqlJoinSpec::JOIN_LEFT, $table);
  }

  public static function join(int $joinType, SqlTable $table): SqlJoin {
    return new SqlJoin($joinType, $table);
  }

  public function getTableAlias(SqlTable $table) {
    $id = $table->id;
    if (!isset($this->tableAliases[$id])) {
      $this->tableAliases[$id] = $table->name[0] . (count($this->tableAliases) + 1);
    }
    return $this->tableAliases[$id];
  }
}
