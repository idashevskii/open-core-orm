<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;

class SqlExprOpBinary extends SqlExpr {
  public const OP_EQ = '=';
  public const OP_NE = '!=';
  public const OP_AND = 'AND';
  public const OP_LIKE = 'LIKE';

  public function __construct(
    public readonly string $op,
    public readonly SqlExpr $left,
    public readonly SqlExpr $right,
  ) {}

  public function buildInto(Sql $result) {
    // special case
    $this->left->buildInto($result);
    $result->sql .= ' ';
    if ($this->op === self::OP_EQ && $this->right instanceof SqlExprValue) {
      $result->sql .= match ($this->right->type) {
        SqlExprValue::TYPE_ARRAY => 'IN',
        SqlExprValue::TYPE_NULL => 'IS',
        default => $this->op,
      };
    } else if ($this->op === self::OP_NE && $this->right instanceof SqlExprValue) {
      $result->sql .= match ($this->right->type) {
        SqlExprValue::TYPE_ARRAY => 'NOT IN',
        SqlExprValue::TYPE_NULL => 'IS NOT',
        default => $this->op,
      };
    } else {
      $result->sql .= $this->op;
    }
    $result->sql .= ' ';
    $this->right->buildInto($result);
  }

  public function traverse(callable $cb) {
    $cb($this);
    $this->left->traverse($cb);
    $this->right->traverse($cb);
  }
}
