<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlField;

final class SqlExprField extends SqlExpr {
  public function __construct(
    public readonly SqlField $field,
  ) {}

  public function buildInto(Sql $result) {
    if ($result->tableAliasesEnabled) {
      $result->sql .= '`' . $result->getTableAlias($this->field->table) . '`.';
    }
    $result->sql .= '`' . $this->field->name . '`';
  }

  public function traverse(callable $cb) {
    $cb($this);
  }
}
