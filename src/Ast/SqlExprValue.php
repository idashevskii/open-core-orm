<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;

final class SqlExprValue extends SqlExpr {
  public const TYPE_NULL = 1;
  public const TYPE_SCALAR = 2;
  public const TYPE_ARRAY = 3;

  public readonly int $type;

  public function __construct(private readonly null|int|float|string|array $value) {
    if ($value === null) {
      $this->type = self::TYPE_NULL;
    } else if (is_array($value)) {
      $this->type = self::TYPE_ARRAY;
    } else {
      $this->type = self::TYPE_SCALAR;
    }
  }

  public function buildInto(Sql $result) {
    if ($this->type === self::TYPE_NULL) {
      $result->sql .= 'NULL';
    } else if ($this->type === self::TYPE_ARRAY) {
      $placeholders = [];
      foreach ($this->value as $value) {
        $placeholders[] = '?';
        $result->params[] = $value;
      }
      $result->sql .= '(' . implode(', ', $placeholders) . ')';
    } else {
      $result->sql .= '?';
      $result->params[] = $this->value;
    }
  }

  public function traverse(callable $cb) {
    $cb($this);
  }
}
