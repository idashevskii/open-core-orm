<?php declare(strict_types=1);

namespace OpenCore\Orm;

use OpenCore\Orm\Annotations\{Field, Table};

final class ObjectDefinition {
  private static array $cache = [];
  private ?array $foreignToProp = null;
  private ?SqlTable $sqlTable = null;
  private ?array $reqDefs = null;
  private ?array $sqlFieldsCache = null;

  private readonly array $props;

  public function __construct(
    private readonly string $table,
    private readonly array $propToFieldMap,
    private readonly ?array $propToForeignMap,
    private readonly ?array $propToExternalMap,
    private readonly ?string $propPrimary,
  ) {
    $this->props = array_keys($propToFieldMap);
  }

  public static function from(string $class): self {
    if (!isset(self::$cache[$class])) {
      self::$cache[$class] = self::buildFromClass($class);
    }
    return self::$cache[$class];
  }

  private static function buildFromClass(string $class): static {
    $propToFieldMap = [];
    $propToForeignMap = null;
    $propToExternalMap = null;
    $propPrimary = null;

    $rClass = new \ReflectionClass($class);
    $clsAttrs = $rClass->getAttributes(Table::class);
    if (!$clsAttrs) {
      throw new \ErrorException("Class $class has not " . Table::class . ' annotation');
    }
    $tableArgs = $clsAttrs[0]->getArguments();
    $sqlTableName = $tableArgs[0] ?? $tableArgs['name'];

    foreach ($rClass->getProperties() as $rProp) {
      /** @var \ReflectionProperty $rProp */
      $propAttrs = $rProp->getAttributes(Field::class);
      if (!$propAttrs) {
        continue;
      }
      $propName = $rProp->getName();
      $propArgs = $propAttrs[0]->getArguments();

      $propToFieldMap[$propName] = $propArgs[0] ?? ($propArgs['name'] ?? $propName);
      if (isset($propArgs['foreignKey'])) {
        $propToForeignMap[$propName] = $propArgs['foreignKey'];
      }
      if (isset($propArgs['externalTable']) && $propArgs['externalTable'] !== $class) {
        $propToExternalMap[$propName] = $propArgs['externalTable'];
      }
      if (isset($propArgs['primaryKey']) && $propArgs['primaryKey']) {
        $propPrimary = $propName;
      }
    }
    return new static($sqlTableName, $propToFieldMap, $propToForeignMap, $propToExternalMap, $propPrimary);
  }

  public function getSqlField(string $prop): SqlField {
    if (!isset($this->sqlFieldsCache[$prop])) {
      if (isset($this->propToExternalMap[$prop])) {
        $this->sqlFieldsCache[$prop] = static::from($this->propToExternalMap[$prop])->getSqlField($prop);
      } else {
        $this->sqlFieldsCache[$prop] = new SqlField($this->getSqlTable(), $this->propToFieldMap[$prop], $prop);
      }
    }
    return $this->sqlFieldsCache[$prop];
  }

  public function getSqlTable(): SqlTable {
    if ($this->sqlTable === null) {
      $this->sqlTable = new SqlTable($this->table);
    }
    return $this->sqlTable;
  }

  public function getSqlPrimary(): SqlField {
    return $this->getSqlField($this->propPrimary);
  }

  /**
   * Returns all definitions, taking in account all external fields in order where first comes repos with less count of dependenicies.
   *
   * It could be helpful for such operations as create/delete, where order matter
   * @return static[]
   */
  public function getRequiredDefinitions(): array {
    if ($this->reqDefs === null) {
      if ($this->propToExternalMap) {
        $externals = [];
        foreach ($this->propToExternalMap as $class) {
          if (!isset($externals[$class])) {
            $extDef = static::from($class);
            $externals[$class] = [$extDef, count($extDef->getRequiredDefinitions())];
          }
        }
        usort($externals, fn (array $od1, array $od2) => $od1[1] - $od2[1]); // sort by count of deps
        $this->reqDefs = array_map(fn (array $od) => $od[0], $externals);
        $this->reqDefs[] = $this;
      } else {
        $this->reqDefs = [$this];
      }
    }
    return $this->reqDefs;
  }

  public function getPrimaryProp() {
    return $this->propPrimary;
  }

  public function getProperties(): array {
    return $this->props;
  }

  /** Non external props only */
  public function getOwnProperties(): array {
    return array_values(array_filter($this->props, fn (string $prop) => !isset($this->propToExternalMap[$prop])));
  }

  public function getSqlFields(?array $propNames = null): array {
    return array_map(fn (string $prop) => $this->getSqlField($prop), $propNames ?? $this->props);
  }

  public function getForeignProps(?array $propNames = null): array {
    return \array_keys($this->propToForeignMap);
  }

  public function getForeignClass(string $prop): string {
    return $this->propToForeignMap[$prop];
  }

  public function getForeignProp(string $foreignClass): string {
    if ($this->foreignToProp === null) {
      $this->foreignToProp = array_flip($this->propToForeignMap);
    }
    return $this->foreignToProp[$foreignClass];
  }
}
