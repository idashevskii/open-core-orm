<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\Utils\SqlUtils;

class SqlExprOpCall extends SqlExpr {
  public function __construct(
    public readonly string $fn,
    public ?array $args = null,
  ) {}

  public function buildInto(Sql $result) {
    $result->sql .= $this->fn . '(';
    if ($this->args !== null) {
      SqlUtils::buildIntoJoined($result, $this->args, ', ');
    }
    $result->sql .= ')';
  }

  public function traverse(callable $cb) {
    $cb($this);
    if ($this->args !== null) {
      foreach ($this->args as $ast) {
        $ast->traverse($cb);
      }
    }
  }
}
