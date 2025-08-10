<?php
// delete_domain.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['id'])) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("DELETE FROM domains WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: index.php');
exit;