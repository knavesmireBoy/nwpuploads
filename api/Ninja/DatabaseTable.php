<?php

namespace Ninja;

class DatabaseTable
{
    protected $pdo;
    protected $table;
    protected $primaryKey;
    protected $className;
    protected $constructorArgs;

    private function processDates($values)
    {
        foreach ($values as $key => $value) {
            if ($value instanceof \DateTime) {
                $values[$key] = $value->format('Y-m-d');
            }
        }
        return $values;
    }

    private function insert(array $values)
    {
        //make sure no spaces in table name: `user `

        $query = 'INSERT INTO `' . $this->table . '` (';
        //$query = "INSERT INTO $tbl (";
        foreach ($values as $key => $value) {
            $query .= '`' . $key . '`,';
        }

        $query = rtrim($query, ',');
        $query .= ') VALUES (';

        foreach ($values as $key => $value) {
            $query .= ':' . $key . ',';
        }
        $query = rtrim($query, ',');
        $query .= ')';
        $stmt = $this->pdo->prepare($query);

        $stmt->execute($values);
        // $res = doPreparedQuery($stmt, $values);
        return $this->pdo->lastInsertId();
    }

    private function updatejoin(array $values, $oldkey)
    {
        $query = 'UPDATE `' . $this->table . '` SET ';
        //technically we'd have a composite key in this situation, but we merely require the FIRST column_name
        $k = $this->primaryKey;
        foreach ($values as $key => $value) {
            $query .= '`' . $key . '` = :' . $key . ',';
        }
        //on TWO COLUMN lookup tables the value of $key will be set the TITLE of the SECOND column at this point
        //we need this: UPDATE `table` SET `first_col` = 34 ,`second_col` = 89 WHERE `first_col` = 34 AND `second_col` = 79
        //the generic upload function can only handle an update if there is just one instance of the first_column value
        $query = rtrim($query, ',');
        $query .= ' WHERE `' . $k . '` = :pk AND `' . $key . '` = :kk';
        $values['pk'] = $values[$k];
        $values['kk'] = $oldkey;
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);
    }
    private function update(array $values)
    {
        $query = 'UPDATE `' . $this->table . '` SET ';
        $k = $this->primaryKey;
        foreach ($values as $key => $value) {
            $query .= '`' . $key . '` = :' . $key . ',';
        }
        $query = rtrim($query, ',');
        $query .= ' WHERE `' . $this->primaryKey . '` = :pk';
        $values['pk'] = $values[$k];
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($values);
    }
    public function __construct(\PDO $pdo, string $table, string $primaryKey, string $className = '\stdClass', array $constructorArgs = [])
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->className = $className;
        $this->constructorArgs = $constructorArgs;
    }

    public function delete($field, $v)
    {
        $stmt = $this->pdo->prepare('DELETE FROM `' . $this->table . '` WHERE `' . $field . '` = :value');

        $values = [
            ':value' => $v
        ];

        $stmt->execute($values);
    }
    public function findAll(?string $orderBy = null, int $limit = 0, int $offset = 0, $mode = \PDO::FETCH_CLASS)
    {
        $query = 'SELECT * FROM ' . $this->table;
        if ($orderBy) {
            $query .= ' ORDER BY ' . $orderBy;
        } else {
            $query .= ' ORDER BY id';
        }
        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }
        if ($offset > 0) {
            $query .= ' OFFSET ' . $offset;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($mode === \PDO::FETCH_CLASS) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constructorArgs);
        }
        return $stmt->fetchAll($mode);
    }

    public function filterNull($col, $flag = false, $orderBy = null, int $limit = 0, int $offset = 0, $mode = \PDO::FETCH_CLASS)
    {
        $nullish = $flag ? ' IS NULL' : ' IS NOT NULL';
        $query = "SELECT * FROM $this->table WHERE $col $nullish";

        if ($orderBy != null) {
            $query .= ' ORDER BY ' . $orderBy;
        }

        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }

        if ($offset > 0) {
            $query .= ' OFFSET ' . $offset;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        if ($mode === \PDO::FETCH_CLASS) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constructorArgs);
        }
        return $stmt->fetchAll($mode);
    }

    public function find(string $column, mixed $value, ?string $orderBy = null, int $limit = 0, int $offset = 0, $mode = \PDO::FETCH_CLASS, $op = ' = :value')
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE ' . $column . $op;
        $parameters = [];
        if (!is_null($value)) {
            $parameters = [
                'value' => $value
            ];
        }

        if ($orderBy != null) {
            $query .= ' ORDER BY ' . $orderBy;
        }

        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }

        if ($offset > 0) {
            $query .= ' OFFSET ' . $offset;
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($parameters);
        if ($mode === \PDO::FETCH_CLASS) {
            return $stmt->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->className, $this->constructorArgs);
        }
        return $stmt->fetchAll($mode);
    }

    public function save(array $record, mixed $arg = null)
    {
            $entity = new $this->className(...$this->constructorArgs);
            //force insert
            if ($arg && is_bool($arg)) {
                return $this->insert($record);
            }
            if ($arg && is_numeric($arg)) {
                return $this->updatejoin($record, $arg);
            }

            if (empty($record[$this->primaryKey])) {
                unset($record[$this->primaryKey]);
                $insertId = $this->insert($record);
                $entity->{$this->primaryKey} = $insertId;
            } else {
                $this->update($record);
            }
            foreach ($record as $key => $value) {
                if (!empty($value)) {
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $entity->$key = $value;
                }
            }
            return $entity;
    }

    public function getEntity()
    {
        return new $this->className(...$this->constructorArgs);
    }

    public function setMinToNull1($table, $colname, $colval)
    {
        $query = "UPDATE $table INNER JOIN (SELECT min(id) AS target from $table where $colname = $colval) AS tmp ON tmp.target = id SET $colname = NULL";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    public function setMinToNull($colname, $colval)
    {
        $query = "UPDATE $this->table INNER JOIN (SELECT min(id) AS target from $this->table where $colname = $colval) AS tmp ON tmp.target = id SET $colname = NULL";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getProp($prop)
    {
        return $this->{$prop};
    }

    public function truncate()
    {
        $query = "TRUNCATE TABLE $this->table";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
    }

    public function setName($name = '')
    {
        $this->table = $name ? $name : $this->table;
    }

    public function getName()
    {
        return $this->table;
    }

    public function trigger()
    {
        $query = "DELIMITER $$ CREATE TRIGGER article_new AFTER INSERT ON polo FOR EACH ROW BEGIN INSERT INTO polo (title) VALUES(NEW.title) END$$ DELIMITER";
        // $stmt = $this->pdo->prepare($query);
        //$stmt->execute();
    }

    public function count($agg, $col = 'id', $mode = \PDO::FETCH_CLASS)
    {
        $t = $this->table;
        $query = "SELECT count($col) from $this->table GROUP BY $agg";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll($mode);
    }

    public function orderBySlot($col, $t, $us, $them, $orderBy = '.id')
    {
        $_us = $this->table . $us;
        $_them = $t . $them;
        $_id = $t . $orderBy;
        $_col = $this->table . $col;
        $query = "SELECT $_col FROM $this->table INNER JOIN $t ON $_us = $_them ORDER BY $_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
