<?php declare(strict_types=1);

namespace OpenCore\Orm\AstBuilder;

use OpenCore\Orm\Ast\SqlExpr;
use OpenCore\Orm\Ast\SqlExprOpBinary;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlBinaryOp {
  public ?SqlExpr $ast;

  public function __construct(private readonly string $op) {
    $this->ast = null;
  }

  public function equals(SqlField $field, mixed $value): self {
    return $this->chain(SqlUtils::fieldBinaryOp(SqlExprOpBinary::OP_EQ, $field, $value));
  }

  public function notEquals(SqlField $field, mixed $value): self {
    return $this->chain(SqlUtils::fieldBinaryOp(SqlExprOpBinary::OP_NE, $field, $value));
  }

  public function like(SqlField $field, string $value): self {
    return $this->chain(SqlUtils::fieldBinaryOp(SqlExprOpBinary::OP_LIKE, $field, SqlUtils::valueToLikeAst($value)));
  }

  public function expr(SqlBinaryOp $expr): self {
    return $this->chain($expr->ast);
  }
  
  private function chain(SqlExpr $addingExpr){
    $this->ast = SqlUtils::chainBinaryExpr(
      $this->op,
      $this->ast,
      $addingExpr,
    );
    return $this;
  }
}
