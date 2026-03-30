<?php
require_once 'db.php';
require_once 'auth.php';

$tisch_id = isset($_GET['tisch']) ? (int)$_GET['tisch'] : 0;
if ($tisch_id <= 0) {
    header("Location: tisch-auswahl.php");
    exit;
}

$name = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $tisch_id = (int)($_POST['tisch'] ?? 0);

    if ($name === '') {
        $error = "Bitte gib einen Namen ein.";
    } elseif ($tisch_id <= 0) {
        $error = "Ungültige Tisch-ID.";
    } else {
        $stmt = $conn->prepare("INSERT INTO kunden (name, tisch_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $tisch_id);
        if ($stmt->execute()) {
            $kunde_id = $stmt->insert_id;
            $stmt->close();
            header("Location: bestellen.php?kunde=" . $kunde_id . "&tisch=" . $tisch_id);
            exit;
        } else {
            $error = "Fehler beim Speichern: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Neuen Kunden anlegen</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #fdfcfb, #e2d1c3);
      min-height: 100vh;
      padding: 2rem;
      text-align: center;
    }

    h1 {
      margin-bottom: 2rem;
    }

    form {
      max-width: 400px;
      margin: 0 auto;
      background: white;
      padding: 1.5rem;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    input {
      width: 90%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }

    button {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 1rem;
      font-size: 1rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.2s;
      width: 100%;
    }

    button:hover {
      background-color: #0056b3;
    }

    .error {
      color: red;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .back-button {
      right: 1rem;
      font-size: 0.8rem;
      text-decoration: none;
    }

    .back-button:hover {
      background-color: #ddd;
    }
  </style>
</head>
<body>
  <a href="kunden-auswahl.php?tisch=<?= $tisch_id ?>" class="back-button" Title="← Zurück" style="
      display: block;
      text-align: left;
      color: #0077cc;
      font-weight: 600;
      margin-bottom: 1rem;
      font-size: 1.2rem;
      cursor: pointer;
      text-decoration: none;
    ">
    &#8592; Zurück
   </a>
  <h1>Neuen Kunden anlegen</h1>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="tisch" value="<?= $tisch_id ?>" />
    <input type="text" name="name" placeholder="Name des Kunden" required value="<?= htmlspecialchars($name) ?>" />
    <button type="submit">Kunde speichern</button>
  </form>
</body>
</html>
