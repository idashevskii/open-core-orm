<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Ast\SqlSelectStatement;
use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\Ast\SqlSelectExpr;
use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Utils\SqlUtils;
use OpenCore\Orm\Ast\SqlAst;
use OpenCore\Orm\Ast\SqlJoinSpec;

final class SqlSelect extends SqlBuilder {

  private readonly SqlSelectStatement $st;

  public function __construct() {
    $this->st = new SqlSelectStatement();
  }

  public function limit(int $limit, int $offset): self {
    $this->st->limit = [$limit, $offset];
    return $this;
  }

  public function from(SqlTable $table): self {
    $this->st->fromTable = $table;
    return $this;
  }

  public function join(SqlJoin $join): self {
    $this->st->joins[] = $join->st;
    return $this;
  }

  public function whereEquals(SqlField $field, mixed $value): self {
    $this->st->whereCondition = SqlUtils::andEqualsCondition($this->st->whereCondition, $field, $value);
    return $this;
  }

  public function whereNotEquals(SqlField $field, mixed $value): self {
    $this->st->whereCondition = SqlUtils::andEqualsCondition($this->st->whereCondition, $field, $value, negative: true);
    return $this;
  }

  public function whereLike(SqlField $field, string $value): self {
    $this->st->whereCondition = SqlUtils::andLike($this->st->whereCondition, $field, $value);
    return $this;
  }

  public function orderBy(SqlField $field, bool $reverse = false): self {
    $this->st->orderBy[] = [new SqlExprField($field), $reverse];
    return $this;
  }

  public function groupBy(SqlField $field): self {
    $this->st->groupBy[] = new SqlExprField($field);
    return $this;
  }

  public function selectCall(SqlCall $expr, string $alias = null): self {
    $this->st->selectExprs[] = new SqlSelectExpr($expr->st, $alias);
    return $this;
  }

  public function fields(array $fields): self {
    foreach ($fields as $field) {
      $this->st->selectExprs[] = new SqlSelectExpr(new SqlExprField($field), $field->alias);
    }
    return $this;
  }

  public function calcFoundRows(): self {
    $this->st->calcFoundRows = true;
    return $this;
  }

  public function build(): Sql {
    $requiredTables = [];
    $selectedTables = [];
    $this->st->traverse(function (SqlAst $ast) use (&$requiredTables, &$selectedTables) {
      if ($ast instanceof SqlExprField) {
        $table = $ast->field->table;
        if (!isset ($requiredTables[$table->id])) {
          $requiredTables[$table->id] = $table;
        }
      } else if ($ast instanceof SqlJoinSpec) {
        $selectedTables[$ast->table->id] = true;
      } else if ($ast instanceof SqlSelectStatement) {
        if ($ast->fromTable !== null) {
          $selectedTables[$ast->fromTable->id] = true;
        }
      }
    });

    foreach ($requiredTables as $tabId => $reqTab) {
      if (isset ($selectedTables[$tabId])) {
        continue;
      }
      if ($this->st->fromTable === null) {
        $this->st->fromTable = $reqTab;
      } else {
        // push natural joins to beginning because other joins can reference to their fields and will fail
        if ($this->st->joins === null) {
          $this->st->joins = [];
        }
        array_unshift($this->st->joins, Sql::naturalJoin($reqTab)->st);
      }
    }
    $ret = new Sql();
    if (count($requiredTables) > 1) {
      $ret->tableAliasesEnabled = true;
    }
    $this->st->buildInto($ret);
    return $ret;
  }

}
