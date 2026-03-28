<?php declare(strict_types=1);

namespace OpenCore\Orm\Ast;

use OpenCore\Orm\Sql;

class SqlExprOpBinary extends SqlExpr {
  public const OP_EQ = '=';
  public const OP_NE = '!=';
  public const OP_AND = 'AND';
  public const OP_OR = 'OR';
  public const OP_LIKE = 'LIKE';

  private const PRIORITY_MAP = [
    self::OP_EQ => 10,
    self::OP_NE => 10,
    self::OP_LIKE => 10,
    self::OP_AND => 20,
    self::OP_OR => 21,
  ];

  public function __construct(
    public readonly string $op,
    public readonly SqlExpr $left,
    public readonly SqlExpr $right,
  ) {}

  public function buildInto(Sql $result) {
    // special case
    $this->buildOperandInto($result, $this->left);
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
    $this->buildOperandInto($result, $this->right);
  }

  private function buildOperandInto(Sql $result, SqlAst $operand) {
    if ($operand instanceof SqlExprOpBinary && self::PRIORITY_MAP[$operand->op] > self::PRIORITY_MAP[$this->op]) {
      $result->sql .= '(';
      $operand->buildInto($result);
      $result->sql .= ')';
    } else {
      $operand->buildInto($result);
    }
  }

  public function traverse(callable $cb) {
    $cb($this);
    $this->left->traverse($cb);
    $this->right->traverse($cb);
  }
}
