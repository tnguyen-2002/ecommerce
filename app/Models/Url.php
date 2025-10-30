<?php

namespace App\Models;

use PDO;

class Url
{
    private PDO $db;

    public int $id = -1;
    public string $shortSlug;
    public string $longUrl;
    public int $userId;
    public array $tags = []; // This will store Tag objects
    public string $createdAt;
    public string $updatedAt;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function create(array $data): bool
    {
        $this->longUrl = $data['longUrl'] ?? $data['long_url'] ?? '';
        $this->userId = $data['userId'] ?? $data['user_id'] ?? 0;
        $this->shortSlug = $this->generateShortSlug();

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Insert the URL first
            $statement = $this->db->prepare(
                'INSERT INTO shortened_urls (short_slug, long_url, user_id, tags, created_at, updated_at)
                VALUES (:short_slug, :long_url, :user_id, :tags::integer[], NOW(), NOW())'
            );

            $result = $statement->execute([
                'short_slug' => $this->shortSlug,
                'long_url' => $this->longUrl,
                'user_id' => $this->userId,
                'tags' => '{}' // Start with empty array
            ]);

            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            $this->id = $this->db->lastInsertId();

            // Handle tags
            if (!empty($data['tags'])) {
                $this->associateTags($data['tags']);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function findById(int $id, ?int $userId = null): ?Url
    {
        $sql = 'SELECT * FROM shortened_urls WHERE id = :id';
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
            $this->loadTags();
            return $this;
        }

        return null;
    }

    public function findBySlug(string $slug, ?int $userId = null): ?Url
    {
        $sql = 'SELECT * FROM shortened_urls WHERE short_slug = :slug';
        $params = ['slug' => $slug];

        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        if ($row) {
            $this->fillFromDbRow($row);
            $this->loadTags();
            return $this;
        }

        return null;
    }

    public function findByUser(int $userId, array $tagIds = []): array
    {
        if (empty($tagIds)) {
            $statement = $this->db->prepare(
                'SELECT * FROM shortened_urls WHERE user_id = :user_id ORDER BY created_at DESC'
            );
            $statement->execute(['user_id' => $userId]);
        } else {
            $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
            $statement = $this->db->prepare(
                "SELECT DISTINCT su.* FROM shortened_urls su 
                WHERE su.user_id = ? 
                 AND EXISTS (
                    SELECT 1 FROM unnest(su.tags) AS tag_id 
                    WHERE tag_id = ANY(ARRAY[{$placeholders}]::integer[])
                )
                ORDER BY su.created_at DESC"
            );

            $params = array_merge([$userId], $tagIds);
            $statement->execute($params);
        }

        $urls = [];
        while ($row = $statement->fetch()) {
            $url = new Url($this->db);
            $url->fillFromDbRow($row);
            $url->loadTags();
            $urls[] = $url;
        }

        return $urls;
    }

    public function update(array $data): bool
    {
        if ($this->id < 0) {
            return false;
        }

        $this->longUrl = $data['longUrl'] ?? $this->longUrl;

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update the URL
            $statement = $this->db->prepare(
                'UPDATE shortened_urls SET long_url = :long_url, updated_at = NOW()
                WHERE id = :id AND user_id = :user_id'
            );

            $result = $statement->execute([
                'long_url' => $this->longUrl,
                'id' => $this->id,
                'user_id' => $this->userId
            ]);

            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            // Handle tags if provided
            if (isset($data['tags'])) {
                $this->updateTags($data['tags']);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function delete(): bool
    {
        if ($this->id < 0) {
            return false;
        }

        $statement = $this->db->prepare(
            'DELETE FROM shortened_urls WHERE id = :id AND user_id = :user_id'
        );

        return $statement->execute([
            'id' => $this->id,
            'user_id' => $this->userId
        ]);
    }

    private function generateShortSlug(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $slug = '';

        do {
            $slug = '';
            for ($i = 0; $i < 10; $i++) {
                $slug .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while ($this->slugExists($slug));

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM shortened_urls WHERE short_slug = :slug'
        );
        $statement->execute(['slug' => $slug]);
        return $statement->fetchColumn() > 0;
    }

    public function fillFromDbRow(array $row): void
    {
        $this->id = $row['id'];
        $this->shortSlug = $row['short_slug'];
        $this->longUrl = $row['long_url'];
        $this->userId = $row['user_id'];
        $this->createdAt = $row['created_at'];
        $this->updatedAt = $row['updated_at'];
    }

    private function loadTags(): void
    {
        $statement = $this->db->prepare(
            'SELECT t.id, t.name, t.user_id, t.created_at, t.updated_at 
            FROM tags t 
            WHERE t.id = ANY(COALESCE((SELECT tags FROM shortened_urls WHERE id = :url_id), ARRAY[]::integer[]))'
        );
        $statement->execute(['url_id' => $this->id]);

        $this->tags = [];
        while ($row = $statement->fetch()) {
            $tag = new Tag($this->db);
            $tag->fillFromDbRow($row);
            $this->tags[] = $tag;
        }
    }

    private function associateTags(array $tagNames): void
    {
        $tagIds = [];
        $tagModel = new Tag($this->db);

        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (!empty($tagName)) {
                $tag = $tagModel->findOrCreate($tagName, $this->userId);
                $tagIds[] = $tag->id;
            }
        }

        if (!empty($tagIds)) {
            $statement = $this->db->prepare(
                'UPDATE shortened_urls SET tags = :tags::integer[] WHERE id = :id'
            );
            $statement->execute([
                'tags' => '{' . implode(',', $tagIds) . '}',
                'id' => $this->id
            ]);
        }
    }

    private function updateTags(array $tagNames): void
    {
        // Clear existing tags
        $statement = $this->db->prepare(
            'UPDATE shortened_urls SET tags = \'{}\'::integer[] WHERE id = :id'
        );
        $statement->execute(['id' => $this->id]);

        // Associate new tags
        $this->associateTags($tagNames);
    }

    public function validate(array $data): array
    {
        $errors = [];

        $longUrl = $data['longUrl'] ?? $data['long_url'] ?? '';

        if (empty($longUrl)) {
            $errors['longUrl'] = 'URL is required.';
        } elseif (!filter_var($longUrl, FILTER_VALIDATE_URL)) {
            $errors['longUrl'] = 'Please enter a valid URL.';
        }

        return $errors;
    }

    public function getFullShortUrl(): string
    {
        $protocol = 'http';

        // Check multiple ways HTTPS might be indicated
        if (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === '1') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (isset($_SERVER['HTTP_X_URL_SCHEME']) && $_SERVER['HTTP_X_URL_SCHEME'] === 'https') ||
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https')
        ) {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        return $protocol . '://' . $host . '/' . $this->shortSlug;
    }

    public function getTagNames(): array
    {
        return array_map(function ($tag) {
            return $tag->name;
        }, $this->tags);
    }
}
