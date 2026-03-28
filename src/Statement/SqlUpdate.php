<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Ast\SqlUpdateStatement;
use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\Ast\SqlExprValue;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Utils\SqlUtils;
use OpenCore\Orm\Sql;

final class SqlUpdate extends SqlBuilder {

  private readonly SqlUpdateStatement $ast;

  public function __construct(SqlTable $table) {
    $this->ast = new SqlUpdateStatement($table);
  }

  public function set(SqlField $field, mixed $value): self {
    $this->ast->assignments[] = [new SqlExprField($field), new SqlExprValue($value)];
    return $this;
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
