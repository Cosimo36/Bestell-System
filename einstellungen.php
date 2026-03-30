<?php
require_once 'db.php';
require_once 'auth.php';

$getraenkeUnterkategorien = ['Alkoholfrei', 'Bier', 'Wein', 'Sekt', 'Cocktails', 'Sonstiges'];
$essenKategorie = 'Essen';
$getraenkeKategorie = 'Getränk';

// API-Handling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT * FROM artikel ORDER BY kategorie, unterkategorie, name");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $preis = $_POST['preis'] ?? '';
        $kategorie = $_POST['kategorie'] ?? '';
        $unterkategorie = $_POST['unterkategorie'] ?? null;

        if (!is_numeric($preis)) {
            echo json_encode(["success" => false, "error" => "Preis muss eine Zahl sein"]);
            exit;
        }
        $preis = floatval($preis);

        if ($name === '' || $preis < 0 || !in_array($kategorie, [$essenKategorie, $getraenkeKategorie])) {
            echo json_encode(["success" => false, "error" => "Ungültige Eingabe"]);
            exit;
        }

        if ($kategorie === $getraenkeKategorie) {
            if (!in_array($unterkategorie, $getraenkeUnterkategorien)) {
                echo json_encode(["success" => false, "error" => "Ungültige Unterkategorie bei Getränk"]);
                exit;
            }
        } else {
            $unterkategorie = null;
        }

        $stmt = $conn->prepare("INSERT INTO artikel (name, kategorie, preis, unterkategorie) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $kategorie, $preis, $unterkategorie);
        $stmt->execute();
        echo json_encode(["success" => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM artikel WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(["success" => true]);
            exit;
        }
        echo json_encode(["success" => false, "error" => "Keine ID"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Einstellungen</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0; padding: 15px 12px 30px;
      background: #f9fafb;
      color: #333;
      line-height: 1.4;
    }
    h1, h2 {
      color: #2c3e50;
      margin-top: 0;
    }
    h1 {
      text-align: center;
      margin-bottom: 25px;
      font-weight: 700;
      font-size: 1.7rem;
    }
    h2 {
      margin-top: 35px;
      margin-bottom: 12px;
      font-weight: 600;
      border-bottom: 2px solid #3498db;
      padding-bottom: 5px;
      font-size: 1.2rem;
    }

    /* Pfeil zurück */
    #backButton {
      position: fixed;
      top: 12px;
      left: 12px;
      background: transparent;
      border: none;
      font-size: 1.8rem;
      cursor: pointer;
      color: #3498db;
      padding: 5px 10px;
      border-radius: 5px;
      transition: background-color 0.2s ease;
      z-index: 1000;
    }
    #backButton:hover {
      background-color: rgba(52, 152, 219, 0.1);
    }

    /* Container */
    #artikelTabelle {
      overflow-x: auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgb(0 0 0 / 0.05);
      padding: 8px;
      max-height: 320px; /* max Höhe begrenzen für kompaktere Ansicht */
      font-size: 0.85rem; /* kleinere Schrift */
    }

    /* Tabelle */
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 500px;
    }
    th, td {
      padding: 8px 10px;
      border-bottom: 1px solid #e1e8f0;
      text-align: left;
      font-size: 0.85rem;
    }
    th {
      background-color: #3498db;
      color: white;
      position: sticky;
      top: 0;
      z-index: 1;
      font-weight: 600;
    }
    tr:hover {
      background-color: #ecf4fb;
    }
    td button {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.8rem;
      transition: background-color 0.3s ease;
    }
    td button:hover {
      background-color: #c0392b;
    }

    /* Formulare */
    form {
      background: white;
      padding: 15px 18px;
      margin-bottom: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgb(0 0 0 / 0.05);
      max-width: 450px;
      margin-left: auto;
      margin-right: auto;
      font-size: 0.9rem;
    }
    form label {
      display: block;
      margin-bottom: 10px;
      font-weight: 600;
      font-size: 0.95rem;
      color: #34495e;
    }
    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 8px 10px;
      margin-top: 4px;
      border: 1.5px solid #bdc3c7;
      border-radius: 6px;
      font-size: 0.9rem;
      transition: border-color 0.3s ease;
    }
    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus {
      outline: none;
      border-color: #3498db;
      box-shadow: 0 0 6px #85c1e9;
    }
    button[type="submit"] {
      background-color: #3498db;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s ease;
      margin-top: 10px;
    }
    button[type="submit"]:hover {
      background-color: #2980b9;
    }

    /* Mobile Anpassungen */
    @media (max-width: 600px) {
      body {
        padding: 12px 10px 25px;
      }
      h1 {
        font-size: 1.5rem;
      }
      h2 {
        font-size: 1.1rem;
      }
      table {
        min-width: unset;
        font-size: 0.8rem;
      }
      form {
        padding: 12px 15px;
        max-width: 100%;
        font-size: 0.85rem;
      }
      input[type="text"],
      input[type="number"],
      select {
        font-size: 0.85rem;
        padding: 7px 9px;
      }
      button[type="submit"] {
        font-size: 0.95rem;
        padding: 9px 16px;
      }
      td button {
        padding: 4px 8px;
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>

  <button id="backButton" aria-label="Zurück zum Hauptmenü" onclick="window.location.href='index.php'">&#8592;</button>

  <h1>Artikelverwaltung</h1>

  <h2>Alle Artikel</h2>
  <div id="artikelTabelle" aria-live="polite" aria-label="Liste der Artikel"></div>

  <h2>Neues Essen hinzufügen</h2>
  <form id="essenForm" aria-label="Formular zum Hinzufügen eines Essens">
    <label for="essenName">Name</label>
    <input type="text" id="essenName" required autocomplete="off" />
    <label for="essenPreis">Preis (€)</label>
    <input type="number" id="essenPreis" step="0.01" min="0" required />
    <button type="submit">Essen hinzufügen</button>
  </form>

  <h2>Neues Getränk hinzufügen</h2>
  <form id="getraenkForm" aria-label="Formular zum Hinzufügen eines Getränks">
    <label for="getraenkName">Name</label>
    <input type="text" id="getraenkName" required autocomplete="off" />
    <label for="getraenkPreis">Preis (€)</label>
    <input type="number" id="getraenkPreis" step="0.01" min="0" required />
    <label for="getraenkUnterkategorie">Unterkategorie</label>
    <select id="getraenkUnterkategorie" required>
      <option value="" disabled selected>Wählen</option>
      <option value="Alkoholfrei">Alkoholfrei</option>
      <option value="Bier">Bier</option>
      <option value="Wein">Wein</option>
      <option value="Sekt">Sekt</option>
      <option value="Cocktails">Cocktails</option>
      <option value="Sonstiges">Sonstiges</option>
    </select>
    <button type="submit">Getränk hinzufügen</button>
  </form>

  <script>
    function ladeArtikel() {
      fetch('einstellungen.php?action=list')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById("artikelTabelle");
          if (!data.length) {
            container.innerHTML = "<p>Keine Artikel gefunden.</p>";
            return;
          }

          let html = "<table><thead><tr><th>Name</th><th>Kategorie</th><th>Unterkategorie</th><th>Preis</th><th>Aktion</th></tr></thead><tbody>";
          data.forEach(row => {
            html += `<tr>
              <td>${row.name}</td>
              <td>${row.kategorie}</td>
              <td>${row.unterkategorie ?? '-'}</td>
              <td>${parseFloat(row.preis).toFixed(2)} €</td>
              <td><button aria-label="Artikel ${row.name} löschen" onclick="loescheArtikel(${row.id})">Löschen</button></td>
            </tr>`;
          });
          html += "</tbody></table>";
          container.innerHTML = html;
        });
    }

    function loescheArtikel(id) {
      if (!confirm("Wirklich löschen?")) return;
      fetch('einstellungen.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${encodeURIComponent(id)}`
      }).then(res => res.json())
        .then(res => {
          if (res.success) {
            ladeArtikel();
          } else {
            alert("Fehler beim Löschen: " + (res.error || ''));
          }
        });
    }

    document.getElementById("essenForm").addEventListener("submit", e => {
      e.preventDefault();
      const name = document.getElementById("essenName").value.trim();
      const preis = document.getElementById("essenPreis").value;

      if (!name || preis === '') {
        alert("Bitte Name und Preis eingeben.");
        return;
      }

      fetch('einstellungen.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `name=${encodeURIComponent(name)}&preis=${encodeURIComponent(preis)}&kategorie=Essen`
      }).then(res => res.json())
        .then(res => {
          if (res.success) {
            document.getElementById("essenForm").reset();
            ladeArtikel();
          } else {
            alert("Fehler: " + (res.error || ''));
          }
        });
    });

    document.getElementById("getraenkForm").addEventListener("submit", e => {
      e.preventDefault();
      const name = document.getElementById("getraenkName").value.trim();
      const preis = document.getElementById("getraenkPreis").value;
      const unterkategorie = document.getElementById("getraenkUnterkategorie").value;

      if (!name || preis === '' || !unterkategorie) {
        alert("Bitte alle Felder ausfüllen.");
        return;
      }

      fetch('einstellungen.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `name=${encodeURIComponent(name)}&preis=${encodeURIComponent(preis)}&kategorie=Getränk&unterkategorie=${encodeURIComponent(unterkategorie)}`
      }).then(res => res.json())
        .then(res => {
          if (res.success) {
            document.getElementById("getraenkForm").reset();
            ladeArtikel();
          } else {
            alert("Fehler: " + (res.error || ''));
          }
        });
    });

    // Beim Laden Artikel anzeigen
    ladeArtikel();
  </script>

</body>
</html>
