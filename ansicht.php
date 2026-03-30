<?php
// ansicht.php

require_once 'db.php';

// Alle Bestellungen mit Kunden- und Tisch-Infos abfragen
$sql = "
    SELECT 
        b.id AS bestellung_id,
        k.name AS kundenname,
        k.telefon,
        k.adresse,
        t.bezeichnung AS tischname,
        b.datum,
        b.status,
        b.lieferzeit,
        b.lieferart
    FROM bestellungen b
    JOIN kunden k ON b.kunde_id = k.id
    LEFT JOIN tische t ON k.tisch_id = t.id
    ORDER BY b.lieferzeit ASC, b.datum DESC
";

$result = $conn->query($sql);
if (!$result) {
    die("Fehler bei der Abfrage: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bestellungen Übersicht</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            background: #f8f8f8;
            color: #333;
        }
        h1 {
            text-align: center;
            color: #444;
        }
        .bestellung {
            background: white;
            margin: 10px auto;
            padding: 15px;
            border-radius: 8px;
            max-width: 600px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .bestellung-header {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        .bestellung-info {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 12px;
        }
        .artikel-liste {
            list-style-type: none;
            padding-left: 0;
            margin-top: 0;
            border-top: 1px solid #eee;
        }
        .artikel-liste li {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.95em;
            display: flex;
            justify-content: space-between;
        }
        .artikel-name {
            font-weight: 500;
        }
        .artikel-status {
            font-style: italic;
            color: #999;
            margin-left: 10px;
            min-width: 60px;
            text-align: right;
        }
        .notiz {
            font-size: 0.85em;
            color: #555;
            margin-left: 10px;
            font-style: italic;
        }
        @media (max-width: 600px) {
            .bestellung {
                margin: 10px 5px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
      <a href="lieferservice.php" style="display:inline-block; margin-bottom:1rem; color:#0077cc; font-weight:600;">&#8592; Zurück</a>
    <h1>Bestellungen Übersicht</h1>

    <?php if ($result->num_rows === 0): ?>
        <p style="text-align:center;">Keine Bestellungen gefunden.</p>
    <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="bestellung">
                <div class="bestellung-header">
                    Bestellung #<?= htmlspecialchars($row['bestellung_id']) ?> von <?= htmlspecialchars($row['kundenname']) ?>
                </div>
                <div class="bestellung-info">
                    Tisch: <?= htmlspecialchars($row['tischname'] ?? 'Kein Tisch') ?><br />
                    Status: <?= htmlspecialchars(ucfirst($row['status'])) ?><br />
                    Datum: <?= htmlspecialchars($row['datum']) ?><br />
                    Lieferart: <?= htmlspecialchars($row['lieferart']) ?><br />
                    Lieferzeit: <?= htmlspecialchars($row['lieferzeit']) ?><br />
                    Telefon: <?= htmlspecialchars($row['telefon']) ?><br />
                    Adresse: <?= htmlspecialchars($row['adresse'] ?: 'Keine Adresse') ?>
                </div>

                <ul class="artikel-liste">
                    <?php
                    // Artikel der Bestellung abfragen
                    $bestellung_id = $row['bestellung_id'];
                    $sql_artikel = "
                        SELECT 
                            ba.id,
                            a.name AS artikelname,
                            ba.status,
                            ba.notiz
                        FROM bestellte_artikel ba
                        LEFT JOIN artikel a ON ba.artikel_id = a.id
                        WHERE ba.bestellung_id = ?
                        ORDER BY ba.id ASC
                    ";
                    $stmt = $conn->prepare($sql_artikel);
                    $stmt->bind_param('i', $bestellung_id);
                    $stmt->execute();
                    $result_artikel = $stmt->get_result();

                    if ($result_artikel->num_rows === 0) {
                        echo "<li>Keine Artikel gefunden.</li>";
                    } else {
                        while ($artikel = $result_artikel->fetch_assoc()) {
                            echo '<li>';
                            echo '<span class="artikel-name">' . htmlspecialchars($artikel['artikelname']) . '</span>';
                            echo '<span class="artikel-status">' . htmlspecialchars(ucfirst($artikel['status'])) . '</span>';
                            if (!empty($artikel['notiz'])) {
                                echo '<div class="notiz">Notiz: ' . htmlspecialchars($artikel['notiz']) . '</div>';
                            }
                            echo '</li>';
                        }
                    }

                    $stmt->close();
                    ?>
                </ul>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

</body>
</html>
