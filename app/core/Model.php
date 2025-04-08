<?php

namespace app\core;

class Model {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findOne($table, $conditions = []) {
        $sql = "SELECT * FROM $table";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function findAll($table, $conditions = []) {
        $sql = "SELECT * FROM $table";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(function($key) {
                return "$key = :$key";
            }, array_keys($conditions)));
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(function($key) {
            return ":$key";
        }, array_keys($data)));

        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $conditions) {
        $set = implode(', ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($data)));

        $where = implode(' AND ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($conditions)));

        $sql = "UPDATE $table SET $set WHERE $where";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(array_merge($data, $conditions));
    }

    public function delete($table, $conditions) {
        $where = implode(' AND ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($conditions)));

        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($conditions);
    }
} 