<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlDeleteStatement extends SqlAst {
  public ?SqlExpr $whereCondition = null;

  public function __construct(
    public readonly SqlTable $fromTable,
  ) {}

  public function buildInto(Sql $result) {
    $result->sql .= 'DELETE FROM ';
    SqlUtils::buildTableFactor($result, $this->fromTable);
    $result->sql .= ' WHERE ';
    $this->whereCondition->buildInto($result);
  }
}
