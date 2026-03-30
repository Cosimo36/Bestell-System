<?php
include 'db.php';

// Gesamtumsätze aus kundenhistorie berechnen
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');
$yearStart = date('Y-01-01');

// Funktion für Umsatz-Abfrage
function getUmsatz($conn, $startDate) {
    $stmt = $conn->prepare("SELECT SUM(gesamtpreis) FROM kundenhistorie WHERE bestell_datum >= ?");
    $stmt->bind_param("s", $startDate);
    $stmt->execute();
    $stmt->bind_result($summe);
    $stmt->fetch();
    $stmt->close();
    return $summe ?? 0;
}

$tagesUmsatz = getUmsatz($conn, $today);
$wochenUmsatz = getUmsatz($conn, $weekStart);
$monatsUmsatz = getUmsatz($conn, $monthStart);
$jahresUmsatz = getUmsatz($conn, $yearStart);

// Offene Bestellungen summieren
$stmt = $conn->prepare("
    SELECT SUM(a.preis) AS offener_gesamtpreis
    FROM bestellungen b
    JOIN bestellte_artikel ba ON b.id = ba.bestellung_id
    JOIN artikel a ON ba.artikel_id = a.id
    WHERE b.bezahlt = 0
");
$stmt->execute();
$stmt->bind_result($offenSumme);
$stmt->fetch();
$stmt->close();
$offenSumme = $offenSumme ?? 0;

// Gesamtumsatz heute (bezahlt + offen)
$gesamtHeute = $tagesUmsatz + $offenSumme;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Statistiken</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: system-ui, sans-serif;
            background: #f9f9f9;
            margin: 0;
            padding: 1rem;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: white;
            padding: 1.2rem;
            border-radius: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .card h2 {
            margin-top: 0;
            font-size: 1.2rem;
            color: #555;
        }
        .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #222;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-button" title="Zurück zur Startseite" style="
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
    <h1>📊 Statistiken</h1>
    <div class="grid">
        <div class="card">
            <h2>Heutiger Umsatz (bezahlt)</h2>
            <div class="value"><?= number_format($tagesUmsatz, 2, ',', '.') ?> €</div>
        </div>
        <div class="card" style="background: #fff8e1;">
            <h2>Offene Rechnungen</h2>
            <div class="value"><?= number_format($offenSumme, 2, ',', '.') ?> €</div>
        </div>
        <div class="card" style="background: #e8f5e9;">
            <h2>💰 Gesamtumsatz heute (inkl. offen)</h2>
            <div class="value"><?= number_format($gesamtHeute, 2, ',', '.') ?> €</div>
        </div>
        <div class="card">
            <h2>Wochenumsatz</h2>
            <div class="value"><?= number_format($wochenUmsatz, 2, ',', '.') ?> €</div>
        </div>
        <div class="card">
            <h2>Monatsumsatz</h2>
            <div class="value"><?= number_format($monatsUmsatz, 2, ',', '.') ?> €</div>
        </div>
        <div class="card">
            <h2>Jahresumsatz</h2>
            <div class="value"><?= number_format($jahresUmsatz, 2, ',', '.') ?> €</div>
        </div>
    </div>
</body>
</html>
