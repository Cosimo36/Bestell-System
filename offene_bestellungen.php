<?php
require 'db.php';
header('Content-Type: text/html; charset=utf-8');

// Einzelnen Artikel auf "erledigt" setzen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']); // ID aus bestellte_artikel
    $stmt = $conn->prepare("UPDATE bestellte_artikel SET status = 'erledigt' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    exit;
}

// Offene Artikelpositionen abrufen
$sql = "
SELECT ba.id AS ba_id, k.name AS kundenname, a.name AS artikel, ba.notiz
FROM bestellungen b
JOIN kunden k ON b.kunde_id = k.id
JOIN bestellte_artikel ba ON ba.bestellung_id = b.id
JOIN artikel a ON ba.artikel_id = a.id
WHERE ba.status = 'offen'
ORDER BY b.id ASC
";

$result = $conn->query($sql);
$zeilen = [];
while ($row = $result->fetch_assoc()) {
    $zeilen[] = $row;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Offene Bestellungen</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
      min-height: 100vh;
      margin: 0;
      padding: 2rem, 2rem;
      display: flex;
      flex-direction: column;
      /* align-items entfernt, damit Pfeil links bleibt */
    }
    .container {
      max-width: 600px;
      margin: 0 auto; /* zentriert nur den Container */
      width: 100%;
    }
    h1 {
      color: #333;
      margin-bottom: 1.5rem;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      background: white;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: #e3e8ef;
      font-weight: 600;
    }
    tr:last-child td {
      border-bottom: none;
    }
    button {
      background-color: #4CAF50;
      border: none;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.2s ease;
    }
    button:hover {
      background-color: #45a049;
    }
    .empty {
      margin-top: 3rem;
      font-size: 1.2rem;
      color: #666;
    }
    a.back-button {
      display: block;
      text-align: left;
      color: #0077cc;
      font-weight: 600;
      margin-bottom: 1rem;
      font-size: 1.2rem;
      cursor: pointer;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <a href="index.php" class="back-button" title="Zurück zur Startseite">&#8592; Zurück</a>

  <div class="container">
    <h1>Offene Artikel</h1>

    <?php if (count($zeilen) === 0): ?>
      <p class="empty">Keine offenen Artikel.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Kunde</th>
            <th>Artikel</th>
            <th>Notiz</th> <!-- neue Spalte -->
            <th>Aktion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($zeilen as $eintrag): ?>
            <tr data-id="<?= $eintrag['ba_id'] ?>">
              <td><?= htmlspecialchars($eintrag['kundenname']) ?></td>
              <td><?= htmlspecialchars($eintrag['artikel']) ?></td>
              <td><?= nl2br(htmlspecialchars($eintrag['notiz'])) ?></td> <!-- Notiz anzeigen, Zeilenumbruch wenn vorhanden -->
              <td><button class="done-btn">Erledigt</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <script>
    document.querySelectorAll('.done-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const row = btn.closest('tr');
        const id = row.dataset.id;

        fetch('offene_bestellungen.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + encodeURIComponent(id)
        })
        .then(res => {
          if (res.ok) {
            row.remove();
            if (document.querySelectorAll('tbody tr').length === 0) {
              document.querySelector('table').remove();
              const msg = document.createElement('p');
              msg.className = 'empty';
              msg.textContent = 'Keine offenen Artikel.';
              document.querySelector('.container').appendChild(msg);
            }
          }
        });
      });
    });
  </script>
</body>
</html>
