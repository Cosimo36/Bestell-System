<?php
include 'db.php';
require_once 'auth.php';

// Tisch-ID aus URL auslesen
$tisch_id = isset($_GET['tisch_id']) ? intval($_GET['tisch_id']) : 0;

if ($tisch_id <= 0) {
    header("Location: tisch.php");
    exit;
}

// Tisch-Bezeichnung holen (für Überschrift)
$stmt = $conn->prepare("SELECT bezeichnung FROM tische WHERE id = ?");
$stmt->bind_param("i", $tisch_id);
$stmt->execute();
$stmt->bind_result($tisch_bezeichnung);
if (!$stmt->fetch()) {
    die("Tisch nicht gefunden.");
}
$stmt->close();

// Kunden für den Tisch holen
$stmt = $conn->prepare("SELECT id, name FROM kunden WHERE tisch_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $tisch_id);
$stmt->execute();
$result = $stmt->get_result();

$kunden = [];
while ($row = $result->fetch_assoc()) {
    $kunden[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kunden am Tisch <?= htmlspecialchars($tisch_bezeichnung) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f9fafb;
      margin: 0;
      padding: 2rem;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    h1 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #333;
    }
    ul {
      list-style: none;
      padding: 0;
    }
    li {
      background: white;
      padding: 1rem 1.5rem;
      margin-bottom: 1rem;
      border-radius: 0.75rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: background-color 0.2s;
      font-weight: 600;
      color: #222;
    }
    li:hover {
      background-color: #e0f0ff;
    }
    a {
      text-decoration: none;
      color: inherit;
      display: block;
    }
  </style>
</head>
<body>
  <a href="tisch.php" style="display:inline-block; margin-bottom:1rem; color:#0077cc; font-weight:600;">&#8592; Tisch wählen</a>
  <h1>Kunden am Tisch „<?= htmlspecialchars($tisch_bezeichnung) ?>“</h1>
  <?php if (count($kunden) === 0): ?>
    <p>Keine Kunden für diesen Tisch gefunden.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($kunden as $kunde): ?>
        <li>
          <a href="kunden_detail.php?id=<?= urlencode($kunde['id']) ?>">
            <?= htmlspecialchars($kunde['name']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</body>
</html>
