<?php declare(strict_types=1);

namespace OpenCore\Orm\Statement;

use OpenCore\Orm\Sql;

abstract class SqlBuilder {
  public function pipe(callable|array $cb): static {
    if (is_array($cb)) {
      $ret = $this;
      foreach ($cb as $cbItem) {
        $ret = $cbItem($ret);
      }
      return $ret;
    }
    return $cb($this);
  }

  abstract public function build(): Sql;
}
