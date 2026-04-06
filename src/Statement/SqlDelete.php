<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Ast\SqlDeleteStatement;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Utils\SqlUtils;
use OpenCore\Orm\Sql;

final class SqlDelete extends SqlBuilder {
  private readonly SqlDeleteStatement $ast;

  public function __construct(SqlTable $table) {
    $this->ast = new SqlDeleteStatement($table);
  }

  public function whereEquals(SqlField $field, mixed $value): self {
    $this->ast->whereCondition = SqlUtils::andEqualsCondition($this->ast->whereCondition, $field, $value);
    return $this;
  }

  public function build(): Sql {
    $ret = new Sql();
    $this->ast->buildInto($ret);
    return $ret;
  }
}
