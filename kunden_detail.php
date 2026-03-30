<?php
include 'db.php';
require_once 'auth.php';

// Kunden-ID aus GET holen
$kundenId = $_GET['id'] ?? null;
if (!$kundenId) {
    die("Keine Kunden-ID angegeben.");
}

// Kunde laden (Name) **Wichtig: vor der POST-Logik laden**
$stmt = $conn->prepare("SELECT name FROM kunden WHERE id = ?");
$stmt->bind_param("i", $kundenId);
$stmt->execute();
$stmt->bind_result($kundeName);
if (!$stmt->fetch()) {
    die("Kunde nicht gefunden.");
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bezahlen'])) {
    // 1. Alle offenen Bestellungen des Kunden laden (jede Bestellung mit Artikeln)
    $stmt = $conn->prepare("
        SELECT b.id AS bestellung_id, b.datum
        FROM bestellungen b
        WHERE b.kunde_id = ? AND b.bezahlt = 0
    ");
    $stmt->bind_param("i", $kundenId);
    $stmt->execute();
    $bestellungenResult = $stmt->get_result();

    // 2. Für jede Bestellung Artikel laden und Übersicht + Gesamtpreis bilden
    $insertStmt = $conn->prepare("
        INSERT INTO kundenhistorie (bestell_datum, artikel_uebersicht, gesamtpreis, kunde_name)
        VALUES (?, ?, ?, ?)
    ");

    while ($bestellung = $bestellungenResult->fetch_assoc()) {
        $bestellungId = $bestellung['bestellung_id'];
        $bestellDatum = $bestellung['datum'];

        // Artikel zur Bestellung laden
        $artikelStmt = $conn->prepare("
            SELECT a.name, COUNT(*) AS anzahl, a.preis
            FROM bestellte_artikel ba
            JOIN artikel a ON ba.artikel_id = a.id
            WHERE ba.bestellung_id = ?
            GROUP BY a.name, a.preis
        ");
        $artikelStmt->bind_param("i", $bestellungId);
        $artikelStmt->execute();
        $artikelResult = $artikelStmt->get_result();

        $artikelListe = [];
        $gesamtpreis = 0;

        while ($artikel = $artikelResult->fetch_assoc()) {
            $name = $artikel['name'];
            $anzahl = $artikel['anzahl'];
            $preis = $artikel['preis'];
            $gesamtpreis += $preis * $anzahl;
            $artikelListe[] = "{$name} ({$anzahl}x)";
        }
        $artikelStmt->close();

        $artikelUebersicht = implode(", ", $artikelListe);

        // Insert in kundenhistorie (hier jetzt $kundeName definiert)
        $insertStmt->bind_param("ssds", $bestellDatum, $artikelUebersicht, $gesamtpreis, $kundeName);
        $insertStmt->execute();
    }
    $stmt->close();
    $insertStmt->close();

    // 3. Kunde löschen (alle Bestellungen durch ON DELETE CASCADE automatisch gelöscht)
    $stmtDel = $conn->prepare("DELETE FROM kunden WHERE id = ?");
    $stmtDel->bind_param("i", $kundenId);
    $stmtDel->execute();
    $stmtDel->close();

    // 4. Weiterleitung
    header("Location: kunden.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_artikel']) && isset($_POST['bestellung_id']) && isset($_POST['artikel_name'])) {
    // Artikel aus Bestellung löschen
    $bestellungId = intval($_POST['bestellung_id']);
    $artikelName = $_POST['artikel_name'];

    // Artikel-ID anhand Name finden
    $stmt = $conn->prepare("SELECT id FROM artikel WHERE name = ?");
    $stmt->bind_param("s", $artikelName);
    $stmt->execute();
    $stmt->bind_result($artikelId);
    if ($stmt->fetch()) {
        $stmt->close();

        // Artikel aus bestellte_artikel löschen
        $stmt2 = $conn->prepare("DELETE FROM bestellte_artikel WHERE bestellung_id = ? AND artikel_id = ? LIMIT 1");
        $stmt2->bind_param("ii", $bestellungId, $artikelId);
        $stmt2->execute();
        $stmt2->close();
    } else {
        $stmt->close();
    }
}

// Kunde laden
$stmt = $conn->prepare("SELECT name FROM kunden WHERE id = ?");
$stmt->bind_param("i", $kundenId);
$stmt->execute();
$stmt->bind_result($kundeName);
if (!$stmt->fetch()) {
    die("Kunde nicht gefunden.");
}
$stmt->close();

// Kontostand dynamisch berechnen aus allen offenen (nicht bezahlten) Bestellungen
$stmt = $conn->prepare("
    SELECT IFNULL(SUM(a.preis),0) AS summe
    FROM bestellungen b
    JOIN bestellte_artikel ba ON b.id = ba.bestellung_id
    JOIN artikel a ON ba.artikel_id = a.id
    WHERE b.kunde_id = ? AND b.bezahlt = 0
");
$stmt->bind_param("i", $kundenId);
$stmt->execute();
$stmt->bind_result($kontostand);
$stmt->fetch();
$stmt->close();

// Bestellungen mit Artikeln laden
$sql = "
    SELECT b.id AS bestellung_id, b.datum, b.bezahlt, b.status,
           a.name AS artikel_name, a.preis
    FROM bestellungen b
    JOIN bestellte_artikel ba ON b.id = ba.bestellung_id
    JOIN artikel a ON ba.artikel_id = a.id
    WHERE b.kunde_id = ?
    ORDER BY b.datum DESC, b.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kundenId);
$stmt->execute();
$result = $stmt->get_result();

// Daten strukturieren: Bestellungen mit zugehörigen Artikeln
$bestellungen = [];
while ($row = $result->fetch_assoc()) {
    $bid = $row['bestellung_id'];
    if (!isset($bestellungen[$bid])) {
        $bestellungen[$bid] = [
            'datum' => $row['datum'],
            'bezahlt' => $row['bezahlt'],
            'status' => $row['status'],
            'artikel' => [],
        ];
    }
    $bestellungen[$bid]['artikel'][] = [
        'name' => $row['artikel_name'],
        'preis' => $row['preis'],
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Kunden-Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      max-width: 600px;
      margin: 2rem auto;
      background: #f9fafb;
      padding: 1rem 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #333;
      margin-bottom: 0.5rem;
    }
    .kontostand {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      color: #0077cc;
    }
    .bestellung {
      background: white;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 1px 5px rgba(0,0,0,0.05);
    }
    .bestellung-header {
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #555;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    ul {
      list-style: none;
      padding-left: 0;
      margin: 0;
    }
    li {
      padding: 0.25rem 0;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    li:last-child {
      border-bottom: none;
    }
    form.delete-artikel-form {
      margin: 0;
    }
    button {
      background-color: #0077cc;
      color: white;
      border: none;
      padding: 0.4rem 1rem;
      font-size: 0.9rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover {
      background-color: #005fa3;
    }
    button.delete-btn {
      background-color: #cc3300;
    }
    button.delete-btn:hover {
      background-color: #992200;
    }
  </style>
</head>
<body>
  <a href="kunden.php" class="back-button" title="Zurück zur Startseite" style="
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    color: #0077cc;
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    cursor: pointer;
  ">
    &#8592; Zurück
  </a>
  <h1><?= htmlspecialchars($kundeName) ?></h1>
  <div class="kontostand">Offener Kontostand: <?= number_format($kontostand, 2, ',', ' ') ?> €</div>

  <h2>Bisherige Bestellungen</h2>

  <?php if (empty($bestellungen)): ?>
    <p>Keine Bestellungen vorhanden.</p>
  <?php else: ?>
    <?php foreach ($bestellungen as $bid => $bestellung): ?>
      <div class="bestellung">
        <div class="bestellung-header">
          <span>Bestellung #<?= $bid ?> vom <?= date('d.m.Y H:i', strtotime($bestellung['datum'])) ?></span>
          <span>(Status: <?= htmlspecialchars($bestellung['status']) ?>, Bezahlt: <?= $bestellung['bezahlt'] ? 'Ja' : 'Nein' ?>)</span>
        </div>
        <ul>
          <?php foreach ($bestellung['artikel'] as $artikel): ?>
            <li>
              <span><?= htmlspecialchars($artikel['name']) ?> – <?= number_format($artikel['preis'], 2, ',', ' ') ?> €</span>
              <?php if (!$bestellung['bezahlt']): ?>
                <form method="POST" class="delete-artikel-form" onsubmit="return confirm('Artikel löschen?');" style="margin:0;">
                  <input type="hidden" name="bestellung_id" value="<?= $bid ?>" />
                  <input type="hidden" name="artikel_name" value="<?= htmlspecialchars($artikel['name']) ?>" />
                  <button type="submit" name="delete_artikel" class="delete-btn">Löschen</button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <form method="POST">
    <button name="bezahlen" type="submit" onclick="return confirm('Kontostand begleichen und Bestellungen löschen?')">Bezahlen</button>
  </form>
</body>
</html>
