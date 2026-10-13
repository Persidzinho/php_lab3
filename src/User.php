<?php
namespace App;

use PDO;

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Создать таблицу, если её нет
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                login TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                bg_color TEXT NOT NULL DEFAULT '#ffffff',
                font_color TEXT NOT NULL DEFAULT '#000000'
            )
        ");
    }

    // Регистрация
    public function register(string $login, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (login, password) VALUES (:login, :password)");
        return $stmt->execute([':login' => $login, ':password' => $hash]);
    }

    // Вход
    public function login(string $login, string $password): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    // Поиск по id (для авто-входа)
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Сохранить настройки
    public function updateSettings(int $id, string $bgColor, string $fontColor): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET bg_color = :bg, font_color = :font WHERE id = :id");
        return $stmt->execute([':bg' => $bgColor, ':font' => $fontColor, ':id' => $id]);
    }
}