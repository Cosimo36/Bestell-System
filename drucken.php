<?php
require __DIR__ . '/vendor/autoload.php';
require_once 'db.php'; // $conn mit MySQLi

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

$kunde_id = $_GET['kunde'] ?? null;
$tisch_id = $_GET['tisch'] ?? null;

function redirectToIndex($message = null) {
    if ($message) {
        // Optional: Nachricht als Query-Parameter übergeben, z.B. ?error=Fehlertext
        $message = urlencode($message);
        header("Location: index.php?error=$message");
    } else {
        header("Location: index.php");
    }
    exit;
}

if (!$kunde_id || !$tisch_id) {
    redirectToIndex("Kunde oder Tisch fehlt");
}

// Kundendaten holen
$stmtKunde = $conn->prepare("SELECT name, adresse, telefon FROM kunden WHERE id = ?");
$stmtKunde->bind_param("i", $kunde_id);
$stmtKunde->execute();
$kundeData = $stmtKunde->get_result()->fetch_assoc();

// Essen-Bestellungen inkl. Notiz holen
$sql = "
    SELECT a.name, a.preis, ba.notiz
    FROM bestellungen b
    JOIN bestellte_artikel ba ON b.id = ba.bestellung_id
    JOIN artikel a ON a.id = ba.artikel_id
    WHERE b.kunde_id = ?
      AND b.status = 'offen'
      AND a.kategorie = 'Essen'
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kunde_id);
$stmt->execute();
$result = $stmt->get_result();

$bestellungen = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['name'] . '|' . ($row['notiz'] ?? '');
    if (!isset($bestellungen[$key])) {
        $bestellungen[$key] = ['name' => $row['name'], 'anzahl' => 0, 'notiz' => $row['notiz']];
    }
    $bestellungen[$key]['anzahl']++;
}

if (empty($bestellungen)) {
    redirectToIndex("Keine offenen Speisen gefunden.");
}

// Druck
try {
    $connector = new NetworkPrintConnector("192.168.178.20", 9100); // IP DEINES DRUCKERS
    $printer = new Printer($connector);

    // Titel groß und zentriert
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setTextSize(2, 2); // Größerer Text
    $printer->text("Bestellung\n");
    $printer->text("Tisch $tisch_id\n");
    $printer->setTextSize(1, 1);
    $printer->text(date("d.m.Y H:i") . "\n");
    $printer->feed();

    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("-----------------------------\n");

    foreach ($bestellungen as $item) {
        $printer->setTextSize(2, 2);
        $printer->text(str_pad($item['anzahl'] . "x ", 5) . $item['name'] . "\n");
        if (!empty($item['notiz'])) {
            $printer->setTextSize(1, 1);
            $printer->text("  → Notiz: " . $item['notiz'] . "\n");
        }
        $printer->feed();
    }

    $printer->setTextSize(1, 1);
    $printer->text("-----------------------------\n");

    // Kundendaten
    if ($kundeData) {
        $printer->text("Name: " . $kundeData['name'] . "\n");
        if (!empty($kundeData['adresse'])) {
            $printer->text("Adresse: " . $kundeData['adresse'] . "\n");
        }
        if (!empty($kundeData['telefon'])) {
            $printer->text("Telefon: " . $kundeData['telefon'] . "\n");
        }
    }

    $printer->feed(2);
    $printer->cut();
    $printer->close();

    // Weiterleitung zur vorherigen Seite
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;

} catch (Exception $e) {
    // Fehler beim Drucken, zurück zu index.php mit Fehlermeldung
    redirectToIndex("Fehler beim Drucken: " . $e->getMessage());
}
