<?php

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

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function selectJoin(array $columns): self
    {
        $this->verb = 'SELECTJOIN';
        return $this;
    }

    public function join(string $table, array $columns, array $join): self
    {
        return $this;
    }

    public function leftJoin(string $table, array $columns, array $join): self
    {
        return $this;
    }

    public function RightJoin(string $table, array $columns, array $join): self
    {
        return $this;
    }

    public function select(array $columns): self
    {
        foreach($columns as $key => $value) {
            if (is_string($key)) {
                $newKey = $this->convertPropertyName2Fieldname($key);
                $newAs = $this->convertPropertyName2Fieldname($value);
                $value = $newKey . ' AS ' . $newAs;
            }
            else {
                $value = $this->convertPropertyName2Fieldname($value);
            }
            $this->columns[] = $value;
        }
        return $this;
    }

    public function insert(array $columns): self
    {
        $this->verb = 'INSERT';
        foreach($columns as $value) {
            $this->columns[] = $this->convertPropertyName2Fieldname($value);
        }
        return $this;
    }

    public function update(array $values): self
    {
        $this->verb = "UPDATE";
        foreach ($values as $key=>$value) {
            if (!is_string($key)) {
                throw new DatabaseException('key is not a valid column name');
            }
            $this->params[] = $value;
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
        $this->params = $values;
        for ($i = 0; $i < count($values); $i++) {
            $this->insertParam[] = '?';
        }
        return $this;
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $column = $this->convertPropertyName2Fieldname($column);
        $this->wheres[] = "$column $operator ?";
        $this->params[] = $value;
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
                foreach($this->columns as $column) {
                    $sql .= $column .' = ?, ';
                }
                //remove 2 last caracters
                $sql  = substr($sql,0,-2);
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
}
