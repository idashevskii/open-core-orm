<?php declare(strict_types=1);

namespace OpenCore\Orm\AstBuilder;

use OpenCore\Orm\SqlField;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\Ast\SqlJoinSpec;
use OpenCore\Orm\Utils\SqlUtils;

final class SqlJoin {

  public readonly SqlJoinSpec $ast;

  public function __construct(int $type, SqlTable $table) {
    $this->ast = new SqlJoinSpec($type, $table);
  }

  public function whereEquals(SqlField $field, mixed $value): self {
    $this->ast->onCondition = SqlUtils::andEqualsCondition($this->ast->onCondition, $field, $value);
    return $this;
  }

}
