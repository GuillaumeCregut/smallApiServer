<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Connector;

class QueryBuilder
{
    private string $table;
    private $columns = [];
    private array $wheres = [];
    private array $params = [];
    private array $insertParam = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private string $verb = 'SELECT';
    private array $on = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function selectJoin(array $columns): self
    {
        $this->verb = 'SELECTJOIN';
        foreach ($columns as $key => $value) {
            if (is_string($key)) {
                $convertedField = $this->convertPropertyName2Fieldname($key);
                $field = "{$this->table}.$convertedField AS {$value}";
            } else {
                $field = $this->table . '.' . $this->convertPropertyName2Fieldname($value);
            }
            $this->columns[] = $field;
        }
        return $this;
    }

    public function join(string $table, array $columns, array $join): self
    {
        $this->on[] = $this->makeJoin('INNER',  $table, $columns, $join);
        return $this;
    }

    public function leftJoin(string $table, array $columns, array $join): self
    {
        $this->on[] = $this->makeJoin('LEFT',  $table, $columns, $join);
        return $this;
    }

    public function RightJoin(string $table, array $columns, array $join): self
    {
        $this->on[] = $this->makeJoin('RIGHT',  $table, $columns, $join);
        return $this;
    }

    public function select(array $columns): self
    {
        foreach ($columns as $key => $value) {
            if (is_string($key)) {
                $newKey = $this->convertPropertyName2Fieldname($key);
                $newAs = $this->convertPropertyName2Fieldname($value);
                $value = $newKey . ' AS ' . $newAs;
            } else {
                $value = $this->convertPropertyName2Fieldname($value);
            }
            $this->columns[] = $value;
        }
        return $this;
    }

    public function insert(array $columns): self
    {
        $this->verb = 'INSERT';
        foreach ($columns as $value) {
            $this->columns[] = $this->convertPropertyName2Fieldname($value);
        }
        return $this;
    }

    public function update(array $values): self
    {
        $this->verb = "UPDATE";
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                throw new DatabaseException('key is not a valid column name');
            }
            $this->insertParam($value);
            $this->columns[] = $this->convertPropertyName2Fieldname($key);
        }
        return $this;
    }

    public function delete(int $id): self
    {
        $this->verb = 'DELETE';
        return $this;
    }

    public function values(array $values): self
    {
        foreach ($values as $value) {
            $this->insertParam($value);
        }
        for ($i = 0; $i < count($values); $i++) {
            $this->insertParam[] = '?';
        }
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $column = $this->convertPropertyName2Fieldname($column);
        $this->wheres[] = "$column $operator ?";
        $this->insertParam($value);
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $column = $this->convertPropertyName2Fieldname($column);
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = "$column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $column = $this->convertPropertyName2Fieldname($column);
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function toSql(): string
    {
        if ($this->verb === 'SELECTJOIN') {
            $sql = 'SELECT ' . implode(', ', $this->columns) . " FROM ";
            $sql .= implode(' ', $this->on);
        }
        switch ($this->verb) {
            case 'SELECT':
                if (empty($this->columns)) {
                    $sql = "SELECT * FROM {$this->table}";
                } else {
                    $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
                }
                if (!empty($this->wheres)) {
                    $sql .= " WHERE " . implode(' AND ', $this->wheres);
                }
                if ($this->orderBy) {
                    $sql .= " ORDER BY {$this->orderBy}";
                }
                if ($this->limit) {
                    $sql .= " LIMIT {$this->limit}";
                }
                break;
            case 'INSERT':
                $sql = "INSERT INTO {$this->table} ";
                $sql .= "(" . implode(', ', $this->columns) . ")";
                $sql .= " VALUES (" . implode(', ', $this->insertParam) . ")";
                break;
            case 'UPDATE':
                $sql = "UPDATE {$this->table} SET ";
                foreach ($this->columns as $column) {
                    $sql .= $column . ' = ?, ';
                }
                //remove 2 last caracters
                $sql  = substr($sql, 0, -2);
                if (!empty($this->wheres)) {
                    $sql .= " WHERE " . implode(' AND ', $this->wheres);
                }
                break;
            case 'DELETE':
                $sql = "DELETE FROM {$this->table} ";
                if (!empty($this->wheres)) {
                    $sql .= "WHERE " . implode(' AND ', $this->wheres);
                }
        }
        return $sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    private function convertPropertyName2Fieldname(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }

    private function makeJoin(string $joinVerb, string $table, array $columns, array $join): string
    {
        if (count($join) > 2) {
            throw new DatabaseException('Array for join is not well formatted');
        }
        foreach ($columns as $key => $value) {
            if (is_string($key)) {
                $convertedField = $this->convertPropertyName2Fieldname($key);
                $field = "{$table}.{$convertedField} AS $value";
            } else {
                $field = $table . '.' . $this->convertPropertyName2Fieldname($value);
            }
            $this->columns[] = $field;
        }
        $tablesJoin = [];
        $columnsJoin = [];
        foreach ($join as $key => $value) {
            if (!is_string($key)) {
                throw new DatabaseException('Array for join is not well formatted');
            }
            $tablesJoin[] = $key;
            $columnsJoin[] = $value;
        }
        $link = '';
        if (count($this->on) === 0) {
            $link .=  $tablesJoin[0] . ' ';
        }

        $link .= $joinVerb . ' JOIN ' . $tablesJoin[1] . ' ON ';
        $link .= $tablesJoin[0] . '.' .  $columnsJoin[0] . ' = ';
        $link .= $tablesJoin[1] . '.' .  $columnsJoin[1];
        return $link;
    }

    private function insertParam(mixed $param): void
    {
        if(is_array($param)) {
            $value = json_encode($param);
        } else {
            $value = $param;
        }
        $this->params[] = $value;
    }
}
