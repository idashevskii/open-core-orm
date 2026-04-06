<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlSelectStatement extends SqlAst {
  public ?SqlTable $fromTable = null;
  public ?array $joins = null;
  public ?array $selectExprs = null;
  public ?SqlExpr $whereCondition = null;
  public ?array $orderBy = null;
  public ?array $groupBy = null;
  public bool $calcFoundRows = false;
  public ?array $limit = null;

  public function buildInto(Sql $result) {
    $result->sql .= 'SELECT';
    if ($this->calcFoundRows) {
      $result->sql .= ' SQL_CALC_FOUND_ROWS';
    }
    if ($this->selectExprs !== null) {
      $result->sql .= ' ';
      SqlUtils::buildIntoJoined($result, $this->selectExprs, ', ');
    }
    if ($this->fromTable !== null) {
      $result->sql .= ' FROM ';
      SqlUtils::buildTableFactor($result, $this->fromTable);
    }
    if ($this->joins !== null) {
      $result->sql .= ' ';
      SqlUtils::buildIntoJoined($result, $this->joins, ' ');
    }

    if ($this->whereCondition !== null) {
      $result->sql .= ' WHERE ';
      $this->whereCondition->buildInto($result);
    }

    if ($this->groupBy !== null) {
      $result->sql .= ' GROUP BY ';
      SqlUtils::buildIntoJoined($result, $this->groupBy, ', ');
    }
    if ($this->orderBy !== null) {
      $result->sql .= ' ORDER BY ';
      SqlUtils::buildIntoJoinedCb($result, $this->orderBy, ', ', function (array $item, Sql $result) {
        list($expr, $reverse) = $item;
        $expr->buildInto($result);
        $result->sql .= ' ' . ($reverse ? 'DESC' : 'ASC');
      });
    }
    if ($this->limit !== null) {
      list($limit, $offset) = $this->limit;
      $result->sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset; // with param throws syntax error because quotes
    }
  }

  public function traverse(callable $cb) {
    $cb($this);
    if ($this->selectExprs !== null) {
      foreach ($this->selectExprs as $ast) {
        $ast->traverse($cb);
      }
    }
    if ($this->joins !== null) {
      foreach ($this->joins as $ast) {
        $ast->traverse($cb);
      }
    }
    if ($this->whereCondition !== null) {
      $this->whereCondition->traverse($cb);
    }
    if ($this->groupBy !== null) {
      foreach ($this->groupBy as $ast) {
        $ast->traverse($cb);
      }
    }
    if ($this->orderBy !== null) {
      foreach ($this->orderBy as list($ast)) {
        $ast->traverse($cb);
      }
    }
  }
}
