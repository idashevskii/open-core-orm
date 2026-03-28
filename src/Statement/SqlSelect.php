<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Ast\SqlAst;
use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\Ast\SqlExprOpBinary;
use OpenCore\Orm\Ast\SqlJoinSpec;
use OpenCore\Orm\Ast\SqlSelectExpr;
use OpenCore\Orm\Ast\SqlSelectStatement;
use OpenCore\Orm\AstBuilder\SqlBinaryOp;
use OpenCore\Orm\AstBuilder\SqlCall;
use OpenCore\Orm\AstBuilder\SqlJoin;
use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlSelect extends SqlBuilder {
  private readonly SqlSelectStatement $ast;

  public function __construct() {
    $this->ast = new SqlSelectStatement();
  }

  public function limit(int $limit, int $offset): self {
    $this->ast->limit = [$limit, $offset];
    return $this;
  }

  public function from(SqlTable $table): self {
    $this->ast->fromTable = $table;
    return $this;
  }

  public function join(SqlJoin $join): self {
    $this->ast->joins[] = $join->ast;
    return $this;
  }

  public function where(SqlBinaryOp $condition): self {
    $this->ast->whereCondition = SqlUtils::chainBinaryExpr(SqlExprOpBinary::OP_AND, $this->ast->whereCondition, $condition->ast);
    return $this;
  }

  public function whereEquals(SqlField $field, mixed $value): self {
    $this->ast->whereCondition = SqlUtils::andEqualsCondition($this->ast->whereCondition, $field, $value);
    return $this;
  }

  public function orderBy(SqlField $field, bool $reverse = false): self {
    $this->ast->orderBy[] = [new SqlExprField($field), $reverse];
    return $this;
  }

  public function groupBy(SqlField $field): self {
    $this->ast->groupBy[] = new SqlExprField($field);
    return $this;
  }

  public function selectCall(SqlCall $expr, string $alias = null): self {
    $this->ast->selectExprs[] = new SqlSelectExpr($expr->ast, $alias);
    return $this;
  }

  public function fields(array $fields): self {
    foreach ($fields as $field) {
      $this->ast->selectExprs[] = new SqlSelectExpr(new SqlExprField($field), $field->alias);
    }
    return $this;
  }

  public function calcFoundRows(): self {
    $this->ast->calcFoundRows = true;
    return $this;
  }

  public function build(): Sql {
    $requiredTables = [];
    $selectedTables = [];
    $this->ast->traverse(function (SqlAst $ast) use (&$requiredTables, &$selectedTables) {
      if ($ast instanceof SqlExprField) {
        $table = $ast->field->table;
        if (!isset($requiredTables[$table->id])) {
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
      if (isset($selectedTables[$tabId])) {
        continue;
      }
      if ($this->ast->fromTable === null) {
        $this->ast->fromTable = $reqTab;
      } else {
        // push natural joins to beginning because other joins can reference to their fields and will fail
        if ($this->ast->joins === null) {
          $this->ast->joins = [];
        }
        array_unshift($this->ast->joins, Sql::naturalJoin($reqTab)->ast);
      }
    }
    $ret = new Sql();
    if (count($requiredTables) > 1) {
      $ret->tableAliasesEnabled = true;
    }
    $this->ast->buildInto($ret);
    return $ret;
  }
}
