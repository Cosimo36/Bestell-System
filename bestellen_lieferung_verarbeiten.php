<?php
require_once 'db.php';
require __DIR__ . '/vendor/autoload.php'; // Druckerlib

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

// Tisch-ID für Abholung/Lieferung
$tischName = "Abholung/Lieferung";
$stmt = $conn->prepare("SELECT id FROM tische WHERE bezeichnung = ?");
$stmt->bind_param("s", $tischName);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Der Tisch '$tischName' wurde nicht gefunden. Bitte anlegen.");
}
$tisch = $result->fetch_assoc();
$tisch_id = $tisch['id'];

// Eingaben holen
$name = trim($_POST['name'] ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$typ = $_POST['typ'] ?? '';
$adresse = trim($_POST['adresse'] ?? '');
$uhrzeit = $_POST['uhrzeit'] ?? '';
$artikel = $_POST['artikel'] ?? [];

if (!$name || !$telefon || !in_array($typ, ['Abholung', 'Lieferung']) || !$uhrzeit) {
    die("Bitte alle Pflichtfelder ausfüllen.");
}

// Kunde einfügen
$stmt = $conn->prepare("INSERT INTO kunden (name, telefon, adresse, tisch_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $name, $telefon, $adresse, $tisch_id);
if (!$stmt->execute()) die("Fehler beim Anlegen des Kunden: " . $stmt->error);
$kunde_id = $stmt->insert_id;

// Bestellung einfügen
$jetzt = date('Y-m-d H:i:s');
$stmt = $conn->prepare("INSERT INTO bestellungen (kunde_id, lieferart, lieferzeit, datum, status) VALUES (?, ?, ?, ?, 'offen')");
$stmt->bind_param("isss", $kunde_id, $typ, $uhrzeit, $jetzt);
if (!$stmt->execute()) die("Fehler beim Anlegen der Bestellung: " . $stmt->error);
$bestellung_id = $stmt->insert_id;

// Artikel speichern
$stmt = $conn->prepare("INSERT INTO bestellte_artikel (bestellung_id, artikel_id, notiz) VALUES (?, ?, ?)");
foreach ($artikel as $art) {
    $artikel_id = intval($art['id']);
    $menge = intval($art['menge']);
    $notiz = trim($art['notiz'] ?? '');
    if ($artikel_id <= 0 || $menge <= 0) continue;

    for ($i = 0; $i < $menge; $i++) {
        $stmt->bind_param("iis", $bestellung_id, $artikel_id, $notiz);
        if (!$stmt->execute()) {
            die("Fehler beim Einfügen eines Artikels: " . $stmt->error);
        }
    }
}
$stmt->close();

// 🔽 DRUCKEN
try {
    // Bestellte Essen holen
    $sql = "
        SELECT a.name, a.preis, ba.notiz
        FROM bestellte_artikel ba
        JOIN artikel a ON a.id = ba.artikel_id
        WHERE ba.bestellung_id = ?
          AND a.kategorie = 'Essen'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bestellung_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $key = $row['name'] . '|' . ($row['notiz'] ?? '');
        if (!isset($items[$key])) {
            $items[$key] = [
                'name' => $row['name'],
                'preis' => floatval($row['preis']),
                'notiz' => $row['notiz'],
                'anzahl' => 0
            ];
        }
        $items[$key]['anzahl']++;
    }

    if (empty($items)) throw new Exception("Keine offenen Speisen gefunden.");

    // Druck starten
    $connector = new NetworkPrintConnector("192.168.178.20", 9100); // Drucker-IP
    $printer = new Printer($connector);

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setTextSize(2, 2);
    $printer->text("Bestellung ($typ)\n");
    $printer->setTextSize(1, 1);
    $printer->text("Abholung/Lieferung: $uhrzeit\n");
    $printer->setTextSize(1, 1);
    $printer->text(date("d.m.Y H:i") . "\n\n");

    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setTextSize(2, 2);
    $printer->text("------------------------\n");

    $gesamtpreis = 0;
    foreach ($items as $item) {
        $zeilenpreis = $item['anzahl'] * $item['preis'];
        $gesamtpreis += $zeilenpreis;
        
        $printer->setTextSize(2, 2);
        $printer->text("{$item['anzahl']}x {$item['name']}\n");

        $printer->setTextSize(1, 1);
        $printer->text("  Einzel: " . number_format($item['preis'], 2, ',', '.') . " EUR");
        $printer->text(" | Gesamt: " . number_format($zeilenpreis, 2, ',', '.') . " EUR\n");

        if (!empty($item['notiz'])) {
            $printer->setTextSize(1, 1);
            $printer->text("  -> Notiz: " . $item['notiz'] . "\n");
        }
        $printer->feed();
    }
    $printer->setTextSize(2, 2);
    $printer->text("------------------------\n");
    $printer->setTextSize(2, 2);
    $printer->text("GESAMT: " . number_format($gesamtpreis, 2, ',', '.') . " EUR\n");
    $printer->text("------------------------\n");
    $printer->setTextSize(1, 1);
    $printer->text("Name: $name\n");
    if (!empty($adresse)) $printer->text("Adresse: $adresse\n");
    $printer->text("Telefon: $telefon\n");

    $printer->feed(2);
    $printer->cut();
    $printer->close();

} catch (Exception $e) {
    echo "Fehler beim Drucken: " . $e->getMessage();
}

// ✅ Zurück zur Startseite
header("Location: index.php");
exit;
