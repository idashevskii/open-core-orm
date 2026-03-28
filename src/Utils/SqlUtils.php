<?php declare(strict_types=1);

namespace OpenCore\Orm\Utils;

use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\Ast\SqlExprValue;
use OpenCore\Orm\Ast\SqlExprOpBinary;
use OpenCore\Orm\Ast\SqlAst;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Ast\SqlExpr;
use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;

final class SqlUtils {
  public static function buildTableFactor(Sql $result, SqlTable $table) {
    $result->sql .= '`' . $table->name . '`';
    if ($result->tableAliasesEnabled) {
      $result->sql .= ' AS `' . $result->getTableAlias($table) . '`';
    }
  }

  public static function valueToAst(mixed $value) {
    if ($value instanceof SqlField) {
      return new SqlExprField($value);
    } else if ($value instanceof SqlExpr) {
      return $value;
    } else {
      return new SqlExprValue($value);
    }
  }

  public static function valueToLikeAst(string $value) {
    return new SqlExprValue('%'.\str_replace(['%', '_'], ['\%', '\_'], $value).'%');
  }

  public static function fieldBinaryOp(string $op, SqlField $field, mixed $value) {
    return new SqlExprOpBinary($op, new SqlExprField($field), self::valueToAst($value));
  }

  public static function chainBinaryExpr(string $chainOp, ?SqlExpr $chainingExpr, SqlExpr $addingExpr): SqlExpr {
    if ($chainingExpr === null) {
      return $addingExpr;
    }
    return new SqlExprOpBinary($chainOp, $chainingExpr, $addingExpr);
  }

  public static function andEqualsCondition(?SqlExpr $condition, SqlField $field, mixed $value): SqlExpr {
    return self::chainBinaryExpr(
      SqlExprOpBinary::OP_AND,
      $condition,
      self::fieldBinaryOp(SqlExprOpBinary::OP_EQ, $field, $value),
    );
  }

  public static function buildIntoJoinedCb(Sql $result, array $list, string $separator, callable $cb) {
    $lastIdx = count($list) - 1;
    foreach ($list as $i => $item) {
      $cb($item, $result);
      if ($i < $lastIdx) {
        $result->sql .= $separator;
      }
    }
  }

  public static function buildIntoJoined(Sql $result, array $astList, string $separator) {
    self::buildIntoJoinedCb($result, $astList, $separator, fn (SqlAst $ast, Sql $result) => $ast->buildInto($result));
  }
}
