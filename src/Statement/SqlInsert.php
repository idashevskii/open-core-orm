<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Ast\SqlInsertStatement;
use OpenCore\Orm\Ast\SqlExprField;
use OpenCore\Orm\SqlTable;
use OpenCore\Orm\SqlField;
use OpenCore\Orm\Sql;
use OpenCore\Orm\Ast\SqlExprValue;

final class SqlInsert extends SqlBuilder {
  private readonly SqlInsertStatement $ast;

  public function __construct(SqlTable $table) {
    $this->ast = new SqlInsertStatement($table);
  }

  public function fields(array $fields): self {
    $this->ast->fields = array_map(fn (SqlField $field) => new SqlExprField($field), $fields);
    return $this;
  }

  public function values(array $values): self {
    $this->ast->values[] = array_map(fn (mixed $value) => new SqlExprValue($value), $values);
    return $this;
  }

  public function build(): Sql {
    $ret = new Sql();
    $this->ast->buildInto($ret);
    return $ret;
  }
}
