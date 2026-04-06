<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlInsertStatement extends SqlAst {
  public array $fields = [];
  public array $values = [];

  public function __construct(
    public readonly SqlTable $fromTable,
  ) {}

  public function buildInto(Sql $result) {
    $result->sql .= 'INSERT INTO ';
    SqlUtils::buildTableFactor($result, $this->fromTable);
    $result->sql .= ' (';
    SqlUtils::buildIntoJoined($result, $this->fields, ', ');
    $result->sql .= ') VALUES ';

    SqlUtils::buildIntoJoinedCb($result, $this->values, ', ', function ($row, $result) {
      $result->sql .= '(';
      SqlUtils::buildIntoJoined($result, $row, ', ');
      $result->sql .= ')';
    });
  }
}
