<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;

final class SqlSelectExpr extends SqlAst {
  public function __construct(
    private readonly SqlExpr $expr,
    private readonly ?string $alias = null,
  ) {}

  public function buildInto(Sql $result) {
    $expr = $this->expr;
    $this->expr->buildInto($result);
    if ($this->alias !== null && !($expr instanceof SqlExprField && $expr->field->name === $this->alias)) {
      $result->sql .= ' AS `' . $this->alias . '`';
    }
  }

  public function traverse(callable $cb) {
    $cb($this);
    $this->expr->traverse($cb);
  }
}
