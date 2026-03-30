<?php
include 'db.php';
require_once 'auth.php';

// Nur Tische holen, die mindestens einen Kunden haben
$sql = "
    SELECT DISTINCT t.id, t.bezeichnung
    FROM tische t
    JOIN kunden k ON k.tisch_id = t.id
    ORDER BY t.bezeichnung ASC
";

$result = $conn->query($sql);

$tische = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tische[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tisch auswählen</title>
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
  <a href="index.php" style="display:inline-block; margin-bottom:1rem; color:#0077cc; font-weight:600; font-size: 1.2rem;">&#8592; Zurück</a>
  <h1>Tisch auswählen</h1>
  <ul>
    <?php foreach ($tische as $tisch): ?>
      <li>
        <a href="kunden.php?tisch_id=<?= urlencode($tisch['id']) ?>">
          <?= htmlspecialchars($tisch['bezeichnung']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</body>
</html>
