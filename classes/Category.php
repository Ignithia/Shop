<?php

/**
 * Category Class
 * Basic CRUD and retrieval for categories used by the shop
 */
class Category
{
    private $id;
    private $name;
    private $description;
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getDescription()
    {
        return $this->description;
    }

    // Setters
    public function setName($name)
    {
        $this->name = $name;
    }
    public function setDescription($desc)
    {
        $this->description = $desc;
    }

    /**
     * Load category by ID
     */
    public function loadById($id)
    {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("SELECT * FROM category WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'] ?? '';
            return true;
        }
        return false;
    }

    /**
     * Create a new category
     */
    public function create($name, $description = '')
    {
        if (!$this->pdo) return false;
        $name = trim($name);
        if (empty($name)) return false;

        // Prevent duplicates
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM category WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) return false;

        $stmt = $this->pdo->prepare("INSERT INTO category (name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $description])) {
            $this->id = $this->pdo->lastInsertId();
            $this->name = $name;
            $this->description = $description;
            return true;
        }
        return false;
    }

    /**
     * Save changes to an existing category
     */
    public function save()
    {
        if (!$this->pdo || !$this->id) return false;
        $stmt = $this->pdo->prepare("UPDATE category SET name = ?, description = ? WHERE id = ?");
        return $stmt->execute([$this->name, $this->description, $this->id]);
    }

    /**
     * Delete a category
     */
    public function delete()
    {
        if (!$this->pdo || !$this->id) return false;
        $stmt = $this->pdo->prepare("DELETE FROM category WHERE id = ?");
        return $stmt->execute([$this->id]);
    }

    /**
     * Get all categories
     */
    public static function getAll($pdo)
    {
        $stmt = $pdo->query("SELECT * FROM category ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find category by name
     * @param PDO $pdo
     * @param string $name
     * @return int|false Category ID or false if not found
     */
    public static function findByName($pdo, $name)
    {
        $stmt = $pdo->prepare("SELECT id FROM category WHERE LOWER(name) = ?");
        $stmt->execute([strtolower($name)]);
        return $stmt->fetchColumn();
    }
}
