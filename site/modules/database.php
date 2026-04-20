<?php

class Database
{
    private $pdo;

    public function __construct($path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function Execute($sql)
    {
        return $this->pdo->exec($sql);
    }

    public function Fetch($sql)
    {
        $statement = $this->pdo->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll();
    }

    public function Create($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(function ($column) {
            return ':' . $column;
        }, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);
        foreach ($data as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }
        $statement->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function Read($table, $id)
    {
        $statement = $this->pdo->prepare(sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', $table));
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function Update($table, $id, $data)
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = $column . ' = :' . $column;
        }

        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $table, implode(', ', $sets));
        $statement = $this->pdo->prepare($sql);

        foreach ($data as $column => $value) {
            $statement->bindValue(':' . $column, $value);
        }
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function Delete($table, $id)
    {
        $statement = $this->pdo->prepare(sprintf('DELETE FROM %s WHERE id = :id', $table));
        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function Count($table)
    {
        $statement = $this->pdo->query(sprintf('SELECT COUNT(*) AS count FROM %s', $table));
        $row = $statement->fetch();

        return $row === false ? 0 : (int) $row['count'];
    }
}
