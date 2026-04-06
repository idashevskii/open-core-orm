<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;

abstract class SqlAst {
  abstract public function buildInto(Sql $result);

  public function traverse(callable $cb) {
    throw new \ErrorException('Abstract');
  }
}
