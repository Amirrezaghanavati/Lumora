<?php

namespace System\Database\Traits;

use System\Database\DBConnection\DBConnection;

trait HasQueryBuilder
{

    private ?string $sql = '';
    protected ?array $where = [];
    private ?array $orderBy = [];
    private ?array $limit = [];

    private ?array $values = [];
    private ?array $bindValues = [];

    protected function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    protected function getSql(): string
    {
        return $this->sql;
    }

    protected function resetSql(): void
    {
        $this->sql = '';
    }

    protected function setWhere($operator, $condition): void
    {
        $this->where[] = ['operator' => $operator, 'condition' => $condition];
    }

    protected function resetWhere(): void
    {
        $this->where = [];
    }

    protected function setOrderBy($orderBy, $expression): void
    {
        $this->orderBy[] = [$this->getAttributeName($orderBy) .' '. $expression];
    }

    protected function resetOrderBy(): void
    {
        $this->where = [];
    }

    protected function setLimit($limit, $offset): void
    {
        $this->limit['limit'] = (int) $limit;
        $this->limit['offset'] = (int) $offset;
    }

    protected function resetLimit(): void
    {
        unset($this->limit['limit'], $this->limit['offset']);
    }

    protected function addValue($attribute, $value): void
    {
        $this->values[$attribute] = $value;
        $this->bindValues[] = $value;
    }

    protected function resetValues(): void
    {
        $this->values = [];
        $this->bindValues = [];
    }

    protected function resetQuery(): void
    {
        $this->resetSql();
        $this->resetWhere();
        $this->resetOrderBy();
        $this->resetLimit();
        $this->resetValues();
    }

    protected function executeQuery(): \PDOStatement|false
    {
        $query = '';
        $query .= $this->sql;
        $query = $this->getWhere($query);
        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY '.implode(', ', $this->orderBy);
        }
        if (!empty($this->limit)) {
            $query .= ' LIMIT '.$this->limit['offset'].', '.$this->limit['limit'];
        }
        $query .= ' ;';
        echo $query. '<hr>';
        return $this->pdoConnection($query);
    }

    protected function getCount(): int|false
    {
        $query = "SELECT COUNT(" . $this->getTableName() . ".*) FROM " . $this->getTableName();
        $query = $this->getWhere($query);
        return $this->pdoConnection($query)->fetchColumn();
    }

    private function pdoConnection($query): false|\PDOStatement
    {
        $pdoInstance = DBConnection::getDBConnectionInstance();
        $statement = $pdoInstance->prepare($query);
        if (count($this->bindValues) > count($this->values)) {
            count($this->bindValues) > 0
                ? $statement->execute($this->bindValues)
                : $statement->execute();
        }else{
            count($this->values) > 0
                ? $statement->execute(array_values($this->values))
                : $statement->execute();
        }
        return $statement;
    }

    /**
     * @param  string  $query
     * @return string
     */
    protected function getWhere(string $query): string
    {
        if (! empty($this->where)) {
            $whereString = '';
            foreach ($this->where as $where) {
                $whereString === ''
                    ? $whereString .= $where['condition']
                    : $whereString .= ' ' . $where['operator'] . ' ' . $where['condition'];
            }
            $query .= ' WHERE '. $whereString;
        }
        return $query;
    }

    protected function getTableName(): string
    {
        return ' `'. $this->table . '`';
    }

    protected function getAttributeName(string $attribute): string
    {
        return ' `'. $this->table . '`' . '.'. '`' .$attribute. '`';

    }


}