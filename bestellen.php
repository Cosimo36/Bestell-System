<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'auth.php';

if (!isset($_GET['kunde']) || !is_numeric($_GET['kunde'])) {
    die("Kein gültiger Kunde ausgewählt.");
}

$kunde_id = (int)$_GET['kunde'];
$tisch_id = (int)$_GET['tisch'];

function formatPreis($preis) {
    return number_format($preis, 2, ',', '') . " €";
}

function holeOffeneBestellung($conn, $kunde_id) {
    $sql = "SELECT id FROM bestellungen WHERE kunde_id = ? AND status = 'offen' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kunde_id);
    $stmt->execute();
    $stmt->bind_result($bestellung_id);
    if ($stmt->fetch()) {
        $stmt->close();
        return $bestellung_id;
    }
    $stmt->close();

    $sql = "INSERT INTO bestellungen (kunde_id) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kunde_id);
    $stmt->execute();
    $neu_id = $stmt->insert_id;
    $stmt->close();
    return $neu_id;
}

function holeKundenDaten($conn, $kunde_id) {
    $sql = "SELECT name, kontostand FROM kunden WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kunde_id);
    $stmt->execute();
    $stmt->bind_result($kunde_name, $kontostand);
    if (!$stmt->fetch()) {
        $stmt->close();
        return null;
    }
    $stmt->close();
    return ['name' => $kunde_name, 'kontostand' => $kontostand];
}

function holeProdukte($conn) {
    $produkte = ['Getränk' => [], 'Essen' => []];
    $sql = "SELECT id, name, kategorie, preis, unterkategorie FROM artikel ORDER BY kategorie, unterkategorie, name";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        if ($row['kategorie'] === 'Getränk') {
            $produkte['Getränk'][$row['unterkategorie']][] = $row;
        } else {
            $produkte['Essen'][] = $row;
        }
    }
    $result->free();
    return $produkte;
}

function holeBestellverlauf($conn, $kunde_id) {
    $sql = "
    SELECT a.name, a.preis, b.datum, ba.notiz
    FROM bestellte_artikel ba
    JOIN bestellungen b ON ba.bestellung_id = b.id
    JOIN artikel a ON ba.artikel_id = a.id
    WHERE b.kunde_id = ?
    ORDER BY b.datum DESC
    LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kunde_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bestellverlauf = [];
    while ($row = $result->fetch_assoc()) {
        $bestellverlauf[] = $row;
    }
    $stmt->close();
    return $bestellverlauf;
}

// POST AJAX: Artikel bestellen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['artikel_id'])) {
    $artikel_id = (int)$_POST['artikel_id'];
    $notiz = isset($_POST['notiz']) ? trim($_POST['notiz']) : '';

    $kunde = holeKundenDaten($conn, $kunde_id);
    if ($kunde === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Kunde nicht gefunden.']);
        exit;
    }

    $bestellung_id = holeOffeneBestellung($conn, $kunde_id);

    $sql = "SELECT preis FROM artikel WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $artikel_id);
    $stmt->execute();
    $stmt->bind_result($artikel_preis);
    if (!$stmt->fetch()) {
        $stmt->close();
        http_response_code(400);
        echo json_encode(['error' => 'Artikel nicht gefunden.']);
        exit;
    }
    $stmt->close();

    $sql = "INSERT INTO bestellte_artikel (bestellung_id, artikel_id, notiz) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $bestellung_id, $artikel_id, $notiz);
    $stmt->execute();
    $stmt->close();

    $neuer_kontostand = $kunde['kontostand'] + $artikel_preis;
    $sql = "UPDATE kunden SET kontostand = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $neuer_kontostand, $kunde_id);
    $stmt->execute();
    $stmt->close();

    $bestellverlauf = holeBestellverlauf($conn, $kunde_id);

    $sql_code = "SELECT zugangscode FROM kunden WHERE id = ?";
    $stmt_code = $conn->prepare($sql_code);
    $stmt_code->bind_param("i", $kunde_id);
    $stmt_code->execute();
    $stmt_code->bind_result($kunde_zugangscode);
    $stmt_code->fetch();
    $stmt_code->close();

    // Zuerst den Namen des Artikels holen
    $sql_art = "SELECT name FROM artikel WHERE id = ?";
    $stmt_art = $conn->prepare($sql_art);
    $stmt_art->bind_param("i", $artikel_id);
    $stmt_art->execute();
    $stmt_art->bind_result($artikel_name);
    $stmt_art->fetch();
    $stmt_art->close();

    // Daten für den Web-Server vorbereiten
    $post_data = [
        'api_key'      => 'Spatzennest2018',
        'kunde_name'   => $kunde['name'],
        'zugangscode'  => $kunde_zugangscode,
        'artikel_name' => $artikel_name,
        'preis'        => $artikel_preis,
        'datum'        => date('Y-m-d H:i:s')
    ];

    // cURL Request an web.de
    $ch = curl_init('https://spatzennest-leutenbach.de/Kundenansicht/api_receive.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Kurzes Timeout
    curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        'kontostand' => formatPreis($neuer_kontostand),
        'bestellverlauf' => $bestellverlauf,
        'meldung' => 'Artikel erfolgreich hinzugefügt!'
    ]);
    exit;
}

// Normale HTML-Ausgabe

$kunde = holeKundenDaten($conn, $kunde_id);
if ($kunde === null) {
    die("Kunde nicht gefunden.");
}

$produkte = holeProdukte($conn);
$bestellverlauf = holeBestellverlauf($conn, $kunde_id);

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Bestellung aufgeben</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f2f5f9;
      padding: 1rem;
      max-width: 600px;
      margin: 0 auto;
      color: #333;
    }
    h1 {
      margin-bottom: 0.5rem;
      text-align: center;
      font-weight: 600;
    }
    .info, .konto, .verlauf {
      background: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    }
    .tabs, .subtabs {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
      gap: 0.5rem;
      flex-wrap: wrap;
    }
    .tab, .subtab {
      padding: 0.6rem 1.2rem;
      background: #e0e0e0;
      text-align: center;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
      user-select: none;
      border-radius: 0.4rem;
      white-space: nowrap;
      color: #555;
    }
    .tab.active, .subtab.active {
      background: #007bff;
      color: white;
      box-shadow: 0 2px 8px rgba(0,123,255,0.4);
    }
    .produkte {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .produkt {
      background: white;
      border-radius: 0.75rem;
      padding: 1.2rem 1rem;
      text-align: center;
      box-shadow: 0 3px 8px rgba(0,0,0,0.1);
      cursor: pointer;
      user-select: none;
      transition: background-color 0.2s, transform 0.15s;
      font-weight: 500;
      color: #222;
      margin-bottom: 1rem;
    }
    .produkt:hover {
      background-color: #cce5ff;
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,123,255,0.25);
    }
    .produkt small {
      color: #555;
      font-weight: 400;
    }
    .meldung {
      background: #d4edda;
      color: #155724;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      border-radius: 0.4rem;
      border: 1px solid #c3e6cb;
      text-align: center;
      font-weight: 600;
      box-shadow: 0 1px 5px rgba(21,87,36,0.3);
    }
    ul {
      padding-left: 1.2rem;
      max-height: 180px;
      overflow-y: auto;
      margin-top: 0.5rem;
      margin-bottom: 0;
    }
    ul li {
      margin-bottom: 0.6rem;
      position: relative;
      padding-left: 22px;
      line-height: 1.3;
      font-weight: 500;
      color: #444;
    }
    ul li::before {
      content: "→";
      position: absolute;
      left: 0;
      color: #007bff;
      font-weight: 700;
      font-size: 18px;
      line-height: 1;
      top: 50%;
      transform: translateY(-50%);
      user-select: none;
    }
  </style>
  <script>
    let aktiveHauptTab = 'getraenke';
    let aktiveGetraenkeSubkategorie = null;

    function switchTab(tab) {
      aktiveHauptTab = tab;
      document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
      document.getElementById('tab-' + tab).classList.add('active');

      document.getElementById('produkte-getraenke-wrapper').style.display = (tab === 'getraenke') ? 'block' : 'none';
      document.getElementById('produkte-essen').style.display = (tab === 'essen') ? 'grid' : 'none';

      if (tab === 'getraenke') {
        const ersteSubtab = document.querySelector('.subtab');
        if (ersteSubtab) {
          switchGetraenkeSubtab(ersteSubtab.dataset.subkat);
        }
      }
    }

    function switchGetraenkeSubtab(subkat) {
      aktiveGetraenkeSubkategorie = subkat;
      document.querySelectorAll('.subtab').forEach(el => el.classList.remove('active'));
      const activeEl = document.querySelector(`.subtab[data-subkat="${subkat}"]`);
      if (activeEl) activeEl.classList.add('active');

      document.querySelectorAll('.getraenke-unterkategorie').forEach(el => {
        el.style.display = el.dataset.subkat === subkat ? 'grid' : 'none';
      });
    }

    async function bestelleArtikel(artikelId) {
        // Hole das Notizfeld des geklickten Artikels
      const artikelDiv = event.target.closest('.produkt');
      let notiz = '';
      if (artikelDiv) {
        const notizInput = artikelDiv.querySelector('.notiz');
        if (notizInput) {
          notiz = notizInput.value.trim();
        }
      }
      try {
        const response = await fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ artikel_id: artikelId, notiz: notiz })
        });
        if (!response.ok) throw new Error("Fehler bei der Bestellung");

        const daten = await response.json();
        if (daten.error) {
          alert("Fehler: " + daten.error);
          return;
        }

        document.getElementById('kontostand').textContent = daten.kontostand;
        ladeBestellverlauf(daten.bestellverlauf);
        zeigeMeldung(daten.meldung);
      } catch (e) {
        alert("Beim Bestellen ist ein Fehler aufgetreten.");
      }
    }

    function ladeBestellverlauf(bestellverlauf) {
      const ul = document.getElementById('bestellverlauf-liste');
      ul.innerHTML = '';
      for (const item of bestellverlauf) {
        const li = document.createElement('li');
        let text = `${item.name} – ${new Date(item.datum).toLocaleDateString()} – ${parseFloat(item.preis).toFixed(2).replace('.', ',')} €`;
        if (item.notiz && item.notiz.trim() !== '') {
          text += `\nNotiz: ${item.notiz}`;
        }
        li.textContent = text;
        ul.appendChild(li);
      }
    }

    let meldungsTimeout = null;
    function zeigeMeldung(text) {
      const meldungEl = document.getElementById('meldung');
      meldungEl.textContent = text;
      meldungEl.style.display = 'block';
      if (meldungsTimeout) clearTimeout(meldungsTimeout);
      meldungsTimeout = setTimeout(() => {
        meldungEl.style.display = 'none';
      }, 3500);
    }

    window.addEventListener('DOMContentLoaded', () => {
      switchTab('getraenke');
    });
  </script>
</head>
<body>

<a href="kunden-auswahl.php?tisch=<?= $tisch_id ?>" style="
    display: inline-flex; 
    align-items: center; 
    font-weight: 600; 
    color: #007bff; 
    text-decoration: none; 
    margin-bottom: 1rem;
    user-select: none;
" 
   onmouseover="this.style.textDecoration='underline'" 
   onmouseout="this.style.textDecoration='none'">
  &#8592;&nbsp;Zurück
</a>

<a href="drucken.php?kunde=<?= $kunde_id ?>&tisch=<?= $tisch_id ?>" target="_blank" style="text-decoration: none;">
  <button style="
    position: fixed;
    top: 1rem;
    right: 1rem;
    background: #007bff;
    border: none;
    border-radius: 8px;
    color: white;
    padding: 12px 28px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease, box-shadow 0.3s ease;
  "
  >
    Essen Drucken
  </button>
</a>



<h1>Bestellung für <strong><?=htmlspecialchars($kunde['name'])?></strong></h1>

<div class="info">
  <div><strong>Kontostand:</strong> <span id="kontostand"><?=formatPreis($kunde['kontostand'])?></span></div>
</div>

<div class="tabs">
  <div id="tab-getraenke" class="tab active" onclick="switchTab('getraenke')">Getränke</div>
  <div id="tab-essen" class="tab" onclick="switchTab('essen')">Essen</div>
</div>

<div id="produkte-getraenke-wrapper">

  <div class="subtabs">
    <?php
      $getraenke_unterkategorien = array_keys($produkte['Getränk']);
      foreach ($getraenke_unterkategorien as $i => $subkat) {
          $activeClass = ($i === 0) ? 'active' : '';
          echo '<div class="subtab '.$activeClass.'" data-subkat="'.htmlspecialchars($subkat).'" onclick="switchGetraenkeSubtab(\''.htmlspecialchars($subkat).'\')">'.htmlspecialchars($subkat).'</div>';
      }
    ?>
  </div>

  <?php foreach ($produkte['Getränk'] as $unterkategorie => $items): ?>
    <div class="produkte getraenke-unterkategorie" data-subkat="<?=htmlspecialchars($unterkategorie)?>" style="display:none;">
      <?php foreach ($items as $artikel): ?>
        <div class="produkt" onclick="bestelleArtikel(<?=$artikel['id']?>)">
          <?=htmlspecialchars($artikel['name'])?><br>
          <small><?=formatPreis($artikel['preis'])?></small>
          <input type="text" class="notiz" placeholder="Notiz / Sonderwunsch" onClick="event.stopPropagation()" />
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

</div>

<div id="produkte-essen" class="produkte" style="display:none;">
  <?php foreach ($produkte['Essen'] as $artikel): ?>
    <div class="produkt" onclick="bestelleArtikel(<?=$artikel['id']?>)">
      <?=htmlspecialchars($artikel['name'])?><br>
      <small><?=formatPreis($artikel['preis'])?></small>
       <input type="text" class="notiz" placeholder="Notiz / Sonderwunsch" onClick="event.stopPropagation()" />
    </div>
  <?php endforeach; ?>
</div>

<div class="meldung" id="meldung" style="display:none;"></div>

<div class="verlauf">
  <h2>Letzte Bestellungen</h2>
  <ul id="bestellverlauf-liste">
    <?php foreach ($bestellverlauf as $item): ?>
      <li>
      <?=htmlspecialchars($item['name'])?> – <?=date('d.m.Y', strtotime($item['datum']))?> – <?=formatPreis($item['preis'])?>
      <?php if (!empty($item['notiz'])): ?>
        <br><small>Notiz: <?=htmlspecialchars($item['notiz'])?></small>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</div>

</body>
</html>
