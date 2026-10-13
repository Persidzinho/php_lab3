<?php
session_start();
require 'vendor/autoload.php';

use App\User;

// Подключение к SQLite (файл users.db создастся сам)
$pdo = new PDO('sqlite:' . __DIR__ . '/users.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$userModel = new User($pdo);
$userModel->createTable();

// Автоматическая авторизация по cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'], $_COOKIE['user_hash'])) {
    $user = $userModel->findById((int)$_COOKIE['user_id']);
    if ($user && md5($user['id'] . $user['password']) === $_COOKIE['user_hash']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['bg_color'] = $user['bg_color'];
        $_SESSION['font_color'] = $user['font_color'];
        setcookie('bg_color', $user['bg_color'], time() + 3600 * 24 * 30);
        setcookie('font_color', $user['font_color'], time() + 3600 * 24 * 30);
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('user_id', '', time() - 3600);
    setcookie('user_hash', '', time() - 3600);
    setcookie('bg_color', '', time() - 3600);
    setcookie('font_color', '', time() - 3600);
    header('Location: index.php');
    exit;
}

// Регистрация
if (isset($_POST['register'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($login && $password) {
        try {
            $userModel->register($login, $password);
            $msg = "Регистрация успешна. Теперь войдите.";
        } catch (PDOException $e) {
            $msg = "Ошибка регистрации: " . $e->getMessage();
        }
    }
}

// Вход
if (isset($_POST['login_btn'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $user = $userModel->login($login, $password);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['bg_color'] = $user['bg_color'];
        $_SESSION['font_color'] = $user['font_color'];

        setcookie('user_id', $user['id'], time() + 3600 * 24 * 30);
        setcookie('user_hash', md5($user['id'] . $user['password']), time() + 3600 * 24 * 30);
        setcookie('bg_color', $user['bg_color'], time() + 3600 * 24 * 30);
        setcookie('font_color', $user['font_color'], time() + 3600 * 24 * 30);

        header('Location: index.php');
        exit;
    } else {
        $msg = "Неверный логин или пароль";
    }
}

// Сохранение настроек
if (isset($_POST['save_settings']) && isset($_SESSION['user_id'])) {
    $bg = $_POST['bg_color'] ?? '#ffffff';
    $font = $_POST['font_color'] ?? '#000000';
    $userModel->updateSettings($_SESSION['user_id'], $bg, $font);
    $_SESSION['bg_color'] = $bg;
    $_SESSION['font_color'] = $font;
    setcookie('bg_color', $bg, time() + 3600 * 24 * 30);
    setcookie('font_color', $font, time() + 3600 * 24 * 30);
    header('Location: index.php');
    exit;
}

// Цвета для страницы
$bgColor = $_SESSION['bg_color'] ?? $_COOKIE['bg_color'] ?? '#ffffff';
$fontColor = $_SESSION['font_color'] ?? $_COOKIE['font_color'] ?? '#000000';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Лаба 3</title>
    <style>
        body { background-color: <?= htmlspecialchars($bgColor) ?>; color: <?= htmlspecialchars($fontColor) ?>; font-family: Arial; }
        form { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<h1>Лабораторная работа №3</h1>

<?php if (isset($msg)): ?>
    <p><b><?= htmlspecialchars($msg) ?></b></p>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>
    <p>Вы вошли как <b><?= htmlspecialchars($_SESSION['login']) ?></b></p>
    <p><a href="?logout=1">Выйти</a></p>

    <h3>Настройки</h3>
    <form method="post">
        <label>Цвет фона: <input type="color" name="bg_color" value="<?= htmlspecialchars($bgColor) ?>"></label><br>
        <label>Цвет шрифта: <input type="color" name="font_color" value="<?= htmlspecialchars($fontColor) ?>"></label><br>
        <button type="submit" name="save_settings">Сохранить настройки</button>
    </form>
<?php else: ?>
    <h3>Регистрация</h3>
    <form method="post">
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit" name="register">Зарегистрироваться</button>
    </form>

    <h3>Вход</h3>
    <form method="post">
        <input type="text" name="login" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit" name="login_btn">Войти</button>
    </form>
<?php endif; ?>
</body>
</html>