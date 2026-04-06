<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlJoinSpec extends SqlAst {
  public const JOIN_INNER = 1;
  public const JOIN_OUTER = 2;
  public const JOIN_NATURAL = 3;

  public ?SqlExpr $onCondition = null;

  public function __construct(
    private readonly int $type,
    public readonly SqlTable $table,
  ) {}

  public function buildInto(Sql $result) {
    $result->sql .= match ($this->type) {
      self::JOIN_INNER => 'INNER',
      self::JOIN_OUTER => 'OUTER',
      self::JOIN_NATURAL => 'NATURAL INNER',
    };
    $result->sql .= ' JOIN ';
    SqlUtils::buildTableFactor($result, $this->table);
    if ($this->onCondition !== null) {
      $result->sql .= ' ON ';
      $this->onCondition->buildInto($result);
    }
  }

  public function traverse(callable $cb) {
    $cb($this);
    if ($this->onCondition !== null) {
      $this->onCondition->traverse($cb);
    }
  }
}
