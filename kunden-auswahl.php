<?php
require_once 'db.php';
require_once 'auth.php';

$tisch_id = isset($_GET['tisch']) ? (int)$_GET['tisch'] : null;

if ($tisch_id <= 0) {
    header("Location: tischauswahl.php");
    exit;
}

$kunden = [];
if ($tisch_id) {
    $stmt = $conn->prepare("SELECT id, name FROM kunden WHERE tisch_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $tisch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $kunden[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kunde auswählen</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #eef2f7, #cfd9df);
      min-height: 100vh;
      padding: 2rem;
      text-align: center;
      margin: 0;
    }

    h1 {
      margin-bottom: 1.5rem;
      color: #333;
      font-size: 2rem;
    }

    .kunden-liste {
      max-width: 400px;
      margin: 0 auto 2rem;
      text-align: left;
      padding: 0;
    }

    .kunden-liste form {
      margin-bottom: 0.8rem;
    }

    .kunden-liste button {
      width: 100%;
      background: white;
      color: #333;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 1rem 1.25rem;
      font-size: 1rem;
      text-align: left;
      transition: background 0.2s;
      cursor: pointer;
      box-sizing: border-box;
    }

    .kunden-liste button:hover {
      background: #e0e0e0;
    }

    .neuer-kunde {
      display: inline-block;
      padding: 1rem 2rem;
      background-color: #28a745;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: background 0.2s;
      font-size: 1rem;
    }

    .neuer-kunde:hover {
      background-color: #218838;
    }

    @media (max-width: 480px) {
      body {
        padding: 1rem;
      }

      h1 {
        font-size: 1.5rem;
      }

      .kunden-liste {
        max-width: 100%;
        padding: 0 0.5rem;
      }

      .kunden-liste button {
        font-size: 1.2rem;
        padding: 1.2rem 1rem;
      }

      .neuer-kunde {
        font-size: 1.2rem;
        padding: 1rem 1.5rem;
      }
    }
  </style>
</head>
<body>
  <a href="tischauswahl.php" class="back-button" title="Zurück zur Tischauswahl" style="
      display: block;
      text-align: left;
      color: #0077cc;
      font-weight: 600;
      margin-bottom: 1rem;
      font-size: 1.2rem;
      cursor: pointer;
      text-decoration: none;
    ">
    &#8592; Zurück zur Tischauswahl
  </a>

  <h1>Kunde auswählen</h1>

  <div class="kunden-liste">
    <?php if (!empty($kunden)): ?>
      <?php foreach ($kunden as $kunde): ?>
        <form method="get" action="bestellen.php">
          <input type="hidden" name="kunde" value="<?= htmlspecialchars($kunde['id']) ?>">
          <input type="hidden" name="tisch" value="<?= htmlspecialchars($tisch_id) ?>">
          <button type="submit"><?= htmlspecialchars($kunde['name']) ?></button>
        </form>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Keine Kunden für diesen Tisch vorhanden.</p>
    <?php endif; ?>
  </div>

  <a href="kunden-anlegen.php?tisch=<?= htmlspecialchars($tisch_id) ?>" class="neuer-kunde">➕ Neuen Kunden anlegen</a>
</body>
</html>
