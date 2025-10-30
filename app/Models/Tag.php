<?php

namespace App\Models;

use PDO;

class Tag
{
    private PDO $db;

    public int $id = -1;
    public string $name;
    public int $userId;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function findByName(string $name, int $userId): ?Tag
    {
        $statement = $this->db->prepare(
            'SELECT * FROM tags WHERE name = :name AND user_id = :user_id'
        );
        $statement->execute(['name' => $name, 'user_id' => $userId]);
        $row = $statement->fetch();

        if ($row) {
            $this->fillFromDbRow($row);
            return $this;
        }

        return null;
    }

    public function findById(int $id, ?int $userId = null): ?Tag
    {
        $sql = 'SELECT * FROM tags WHERE id = :id';
        $params = ['id' => $id];

        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        if ($row) {
            $this->fillFromDbRow($row);
            return $this;
        }

        return null;
    }

    public function findByUser(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM tags WHERE user_id = :user_id ORDER BY name ASC'
        );
        $statement->execute(['user_id' => $userId]);

        $tags = [];
        while ($row = $statement->fetch()) {
            $tag = new Tag($this->db);
            $tag->fillFromDbRow($row);
            $tags[] = $tag;
        }

        return $tags;
    }

    public function create(string $name, int $userId): bool
    {
        $this->name = $name;
        $this->userId = $userId;

        $statement = $this->db->prepare(
            'INSERT INTO tags (name, user_id, created_at, updated_at)
             VALUES (:name, :user_id, NOW(), NOW())'
        );

        $result = $statement->execute([
            'name' => $this->name,
            'user_id' => $this->userId
        ]);

        if ($result) {
            $this->id = $this->db->lastInsertId();
            $this->createdAt = date('Y-m-d H:i:s');
            $this->updatedAt = date('Y-m-d H:i:s');
        }

        return $result;
    }

    public function update(string $name): bool
    {
        if ($this->id < 0) {
            return false;
        }

        $this->name = $name;

        $statement = $this->db->prepare(
            'UPDATE tags SET name = :name, updated_at = NOW() WHERE id = :id AND user_id = :user_id'
        );

        $result = $statement->execute([
            'name' => $this->name,
            'id' => $this->id,
            'user_id' => $this->userId
        ]);

        if ($result) {
            $this->updatedAt = date('Y-m-d H:i:s');
        }

        return $result;
    }

    public function delete(): bool
    {
        if ($this->id < 0) {
            return false;
        }

        // First, remove this tag from all URLs that use it (only for this user)
        $statement = $this->db->prepare(
            'SELECT id, tags FROM shortened_urls WHERE user_id = :user_id AND :tag_id = ANY(tags)'
        );
        $statement->execute(['user_id' => $this->userId, 'tag_id' => $this->id]);

        while ($row = $statement->fetch()) {
            // Remove the tag from the array
            $tags = $row['tags'];
            $tags = array_values(array_filter(explode(',', trim($tags, '{}')), function ($tagId) {
                return $tagId != $this->id;
            }));

            // Update the URL with the new tags array
            $updateStatement = $this->db->prepare(
                'UPDATE shortened_urls SET tags = :tags WHERE id = :url_id'
            );
            $updateStatement->execute([
                'tags' => '{' . implode(',', $tags) . '}',
                'url_id' => $row['id']
            ]);
        }

        // Then delete the tag (only if it belongs to this user)
        $statement = $this->db->prepare(
            'DELETE FROM tags WHERE id = :id AND user_id = :user_id'
        );

        return $statement->execute(['id' => $this->id, 'user_id' => $this->userId]);
    }

    public function findOrCreate(string $name, int $userId): Tag
    {
        $existingTag = $this->findByName($name, $userId);
        if ($existingTag) {
            return $existingTag;
        }

        $this->create($name, $userId);
        return $this;
    }

    public function validate(array $data, int $userId): array
    {
        $errors = [];

        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            $errors['name'] = 'Tag name is required.';
        } elseif (strlen($name) > 255) {
            $errors['name'] = 'Tag name must be less than 255 characters.';
        } else {
            // Check if name already exists for this user (excluding current tag if updating)
            $existingTag = $this->findByName($name, $userId);
            if ($existingTag && $existingTag->id !== $this->id) {
                $errors['name'] = 'A tag with this name already exists.';
            }
        }

        return $errors;
    }

    public function fillFromDbRow(array $row): void
    {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->userId = $row['user_id'];
        $this->createdAt = $row['created_at'];
        $this->updatedAt = $row['updated_at'];
    }
}
