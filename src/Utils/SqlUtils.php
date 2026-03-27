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

  public static function andEqualsCondition(?SqlExpr $condition, SqlField $field, mixed $value, bool $negative = false): SqlExpr {
    if ($value instanceof SqlField) {
      $right = new SqlExprField($value);
    } else if ($value instanceof SqlExpr) {
      $right = $value;
    } else {
      $right = new SqlExprValue($value);
    }
    $exprEq = new SqlExprOpBinary($negative ? SqlExprOpBinary::OP_NE : SqlExprOpBinary::OP_EQ, new SqlExprField($field), $right);
    if ($condition === null) {
      return $exprEq;
    }
    return new SqlExprOpBinary(SqlExprOpBinary::OP_AND, $condition, $exprEq);
  }

  public static function andLike(?SqlExpr $condition, SqlField $field, string $value): SqlExpr {
    $value = '%'.\str_replace(['%', '_'], ['\%', '\_'], $value).'%';
    $exprEq = new SqlExprOpBinary(SqlExprOpBinary::OP_LIKE, new SqlExprField($field), new SqlExprValue($value));
    if ($condition === null) {
      return $exprEq;
    }
    return new SqlExprOpBinary(SqlExprOpBinary::OP_AND, $condition, $exprEq);
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
