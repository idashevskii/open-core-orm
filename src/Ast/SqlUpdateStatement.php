<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlUpdateStatement extends SqlAst {
  public ?SqlExpr $whereCondition = null;
  public array $assignments = [];

  public function __construct(
    public readonly SqlTable $fromTable,
  ) {}

  public function buildInto(Sql $result) {
    $result->sql .= 'UPDATE ';
    SqlUtils::buildTableFactor($result, $this->fromTable);
    $result->sql .= ' SET ';
    SqlUtils::buildIntoJoinedCb($result, $this->assignments, ', ', function ($assignment, $result) {
      list($field, $value) = $assignment;
      $field->buildInto($result);
      $result->sql .= ' = ';
      $value->buildInto($result);
    });
    $result->sql .= ' WHERE ';
    $this->whereCondition->buildInto($result);
  }
}
