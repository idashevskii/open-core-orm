<?php declare(strict_types=1);

namespace OpenCore\Orm\AstBuilder;

use OpenCore\Orm\SqlField;
use OpenCore\Orm\Ast\SqlExprOpCall;
use OpenCore\Orm\Ast\SqlExprField;

final class SqlCall {

  public readonly SqlExprOpCall $ast;

  public function __construct(string $fn) {
    $this->ast = new SqlExprOpCall($fn);
  }

  public function withFieldArg(SqlField $field): self {
    $this->ast->args[] = new SqlExprField($field);
    return $this;
  }

}
