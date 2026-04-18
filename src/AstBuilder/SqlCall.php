<?php declare(strict_types=1);

namespace OpenCore\Orm\AstBuilder;

use OpenCore\Orm\Ast\SqlExpr;
use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\Ast\SqlExprOpCall;
use OpenCore\Orm\SqlField;

final class SqlCall {
  public readonly SqlExprOpCall $ast;

  public function __construct(string $fn) {
    $this->ast = new SqlExprOpCall($fn);
  }

  public function withFieldArg(SqlField $field): self {
    $this->ast->args[] = new SqlExprField($field);
    return $this;
  }

  public function withArg(SqlExpr $arg): self {
    $this->ast->args[] = $arg;
    return $this;
  }
}
