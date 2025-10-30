<?php

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public int $id = -1;
    public string $email;
    public string $name;
    public string $password;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function where(string $column, string $value): User
    {
        $statement = $this->db->prepare("select * from users where $column = :value");
        $statement->execute(['value' => $value]);
        $row = $statement->fetch();
        if ($row) {
            $this->fillFromDbRow($row);
        }
        return $this;
    }

    public function save(): bool
    {
        $result = false;

        if ($this->id >= 0) {
            $statement = $this->db->prepare(
                'update users set email = :email, name = :name, password = :password,
          updated_at = now() where id = :id'
            );
            $result = $statement->execute([
                'id' => $this->id,
                'email' => $this->email,
                'name' => $this->name,
                'password' => $this->password
            ]);
        } else {
            $statement = $this->db->prepare(
                'insert into users (email, name, password, created_at, updated_at)
          values (:email, :name, :password, now(), now())'
            );
            $result = $statement->execute([
                'email' => $this->email,
                'name' => $this->name,
                'password' => $this->password
            ]);
            if ($result) {
                $this->id = $this->db->lastInsertId();
            }
        }

        return $result;
    }

    public function fill(array $data): User
    {
        $this->email = $data['email'];
        $this->name = $data['name'];
        $this->password = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this;
    }

    private function fillFromDbRow(array $row)
    {
        $this->id = $row['id'];
        $this->email = $row['email'];
        $this->name = $row['name'];
        $this->password = $row['password'];
    }

    private function isEmailInUse(string $email): bool
    {
        $statement = $this->db->prepare('select count(*) from users where email = :email');
        $statement->execute(['email' => $email]);
        return $statement->fetchColumn() > 0;
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (!$data['email']) {
            $errors['email'] = 'Invalid email.';
        } elseif ($this->isEmailInUse($data['email'])) {
            $errors['email'] = 'Email already in use.';
        }

        if (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        } elseif ($data['password'] != $data['password_confirmation']) {
            $errors['password'] = 'Password confirmation does not match.';
        }

        return $errors;
    }
}
