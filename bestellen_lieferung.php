<?php
require_once 'db.php';

$artikelResult = $conn->query("SELECT * FROM artikel WHERE kategorie = 'Essen'");
$artikel = [];
while ($row = $artikelResult->fetch_assoc()) {
    $artikel[] = $row;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lieferung oder Abholung</title>
    <style>
/* Reset für bessere Konsistenz */
* {
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #fff;
  padding: 1rem;
  margin: 0;
  color: #222;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.container {
  max-width: 480px;
  margin: 0 auto 3rem;
  background: #fff;
  padding: 1.8rem 1.5rem 2.5rem;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgb(0 0 0 / 0.07);
  border: 1px solid #e1e8ed;
}

h2 {
  font-weight: 700;
  font-size: 1.8rem;
  text-align: center;
  margin-bottom: 1.5rem;
  color: #007bff;
}

a[href="lieferservice.php"] {
  display: inline-block;
  margin-bottom: 1.5rem;
  color: #007bff;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.25s ease;
}
a[href="lieferservice.php"]:hover {
  color: #0056b3;
  text-decoration: underline;
}

.form-group {
  margin-bottom: 1.4rem;
}

label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.4rem;
  font-size: 1rem;
  color: #444;
}

input[type="text"],
input[type="tel"],
input[type="time"],
select,
textarea,
input[type="number"] {
  width: 100%;
  padding: 0.55rem 0.75rem;
  font-size: 1rem;
  border-radius: 8px;
  border: 1.8px solid #ced4da;
  transition: border-color 0.25s ease, box-shadow 0.25s ease;
  background-color: #fefefe;
  font-weight: 400;
  font-family: inherit;
  resize: vertical;
}

input[type="text"]:focus,
input[type="tel"]:focus,
input[type="time"]:focus,
select:focus,
textarea:focus,
input[type="number"]:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 6px rgba(0,123,255,0.4);
  background-color: #fff;
}

textarea {
  min-height: 80px;
  line-height: 1.4;
}

hr {
  border: none;
  border-top: 1.2px solid #e9ecef;
  margin: 2rem 0 1.5rem;
}

h3 {
  font-weight: 600;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  color: #343a40;
}

.artikel-button {
  display: block;
  width: 100%;
  padding: 1rem 1.2rem;
  margin: 0.45rem 0;
  background: #f1f5f9;
  border: 1.5px solid #d1d5db;
  border-radius: 10px;
  cursor: pointer;
  font-size: 1.1rem;
  font-weight: 600;
  text-align: left;
  color: #212529;
  transition: all 0.3s ease;
  box-shadow: inset 0 0 0 0 transparent;
}

.artikel-button:hover,
.artikel-button:focus {
  background: #007bff;
  border-color: #007bff;
  color: white;
  box-shadow: inset 0 0 8px rgb(0 123 255 / 0.4);
}

button[type="submit"] {
  background: #007bff;
  color: white;
  padding: 1.1rem 0;
  border: none;
  border-radius: 10px;
  font-size: 1.15rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 2rem;
  width: 100%;
  box-shadow: 0 6px 12px rgb(0 123 255 / 0.45);
  transition: background 0.25s ease;
}

button[type="submit"]:hover,
button[type="submit"]:focus {
  background: #0056b3;
  box-shadow: 0 8px 18px rgb(0 86 179 / 0.7);
}

#selectedArtikelList {
  margin-top: 1rem;
  padding: 12px 14px;
  background: #e9ecef;
  border-radius: 10px;
  max-height: 210px;
  overflow-y: auto;
  font-size: 0.95rem;
  color: #212529;
  font-weight: 600;
}

#selectedArtikelList p {
  margin: 0;
  font-weight: 400;
  color: #6c757d;
}

#selectedArtikelList ul {
  list-style: none;
  padding-left: 0;
  margin: 0;
}

#selectedArtikelList li {
  background: white;
  margin-bottom: 0.5rem;
  padding: 10px 14px;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 4px rgb(0 0 0 / 0.05);
  word-break: break-word;
}

#selectedArtikelList li button {
  background: #dc3545;
  border: none;
  color: white;
  border-radius: 6px;
  padding: 3px 9px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s ease;
}

#selectedArtikelList li button:hover,
#selectedArtikelList li button:focus {
  background: #a71d2a;
}

/* Modal */
.modal {
  display: none;
  position: fixed;
  z-index: 1500;
  left: 0; top: 0;
  width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.55);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  overflow-y: auto;
  padding: 1rem;
}

.modal-content {
  background: white;
  padding: 2rem 2rem 2.5rem;
  margin: 10% auto 0 auto;
  max-width: 400px;
  border-radius: 18px;
  position: relative;
  box-sizing: border-box;
  box-shadow: 0 12px 35px rgb(0 0 0 / 0.15);
  animation: slideDown 0.25s ease forwards;
}

@keyframes slideDown {
  from {opacity: 0; transform: translateY(-20px);}
  to {opacity: 1; transform: translateY(0);}
}

.close {
  position: absolute;
  right: 18px;
  top: 14px;
  font-size: 28px;
  font-weight: 900;
  cursor: pointer;
  color: #555;
  transition: color 0.25s ease;
  user-select: none;
}
.close:hover,
.close:focus {
  color: #000;
}

#modalArtikelName {
  font-weight: 700;
  font-size: 1.4rem;
  margin-bottom: 1.2rem;
  color: #007bff;
}

label[for="mengeInput"],
label[for="notizInput"] {
  font-weight: 600;
  margin-bottom: 0.3rem;
  display: block;
  color: #333;
}

#mengeInput, #notizInput {
  font-size: 1rem;
  border-radius: 10px;
  border: 1.8px solid #ced4da;
  padding: 0.5rem 0.75rem;
  width: 100%;
  margin-bottom: 1.3rem;
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
  resize: vertical;
}

#mengeInput:focus,
#notizInput:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 8px rgb(0 123 255 / 0.4);
}

.modal-content button {
  background: #007bff;
  border: none;
  border-radius: 10px;
  color: white;
  font-weight: 700;
  font-size: 1.1rem;
  padding: 0.75rem 0;
  cursor: pointer;
  width: 100%;
  transition: background 0.3s ease;
  box-shadow: 0 6px 14px rgb(0 123 255 / 0.5);
}

.modal-content button:hover,
.modal-content button:focus {
  background: #0056b3;
  box-shadow: 0 8px 18px rgb(0 86 179 / 0.7);
}

/* Responsive Text and Layout Tweaks */
@media (max-width: 400px) {
  .container {
    padding: 1.2rem 1rem 2rem;
  }
  h2 {
    font-size: 1.6rem;
  }
  .artikel-button {
    font-size: 1rem;
    padding: 0.85rem 1rem;
  }
  button[type="submit"] {
    font-size: 1rem;
    padding: 1rem 0;
  }
  #selectedArtikelList li {
    font-size: 0.9rem;
  }
}
    </style>
    <script>
        let currentArtikelId = null;
        let currentArtikelName = "";
        // Objekt für ausgewählte Artikel: id => {menge, notiz, name}
        let selectedArtikel = {};

        function openModal(id, name) {
            currentArtikelId = id;
            currentArtikelName = name;
            document.getElementById("modalArtikelName").textContent = name;

            // Falls Artikel schon ausgewählt, Werte im Modal vorfüllen
            if (selectedArtikel[id]) {
                document.getElementById("mengeInput").value = selectedArtikel[id].menge;
                document.getElementById("notizInput").value = selectedArtikel[id].notiz;
            } else {
                document.getElementById("mengeInput").value = 1;
                document.getElementById("notizInput").value = "";
            }

            document.getElementById("modal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        function updateSelectedList() {
            const listContainer = document.getElementById("selectedArtikelList");
            listContainer.innerHTML = "";

            if (Object.keys(selectedArtikel).length === 0) {
                listContainer.innerHTML = "<p>Keine Speisen ausgewählt.</p>";
                return;
            }

            const ul = document.createElement("ul");
            for (const id in selectedArtikel) {
                const artikel = selectedArtikel[id];
                const li = document.createElement("li");
                li.textContent = `${artikel.name} – Menge: ${artikel.menge}${artikel.notiz ? " – Notiz: " + artikel.notiz : ""}`;

                // Entfernen-Button
                const delBtn = document.createElement("button");
                delBtn.textContent = "X";
                delBtn.onclick = function() {
                    delete selectedArtikel[id];
                    updateSelectedList();
                    removeHiddenInputs(id);
                };
                li.appendChild(delBtn);
                ul.appendChild(li);
            }
            listContainer.appendChild(ul);
        }

        function removeHiddenInputs(id) {
            const container = document.getElementById("selectedArtikel");
            const inputsToRemove = container.querySelectorAll(`[data-artikel-id='${id}']`);
            inputsToRemove.forEach(el => el.remove());
        }

        function addArtikel() {
            const menge = parseInt(document.getElementById("mengeInput").value);
            const notiz = document.getElementById("notizInput").value.trim();

            if (isNaN(menge) || menge < 1) {
                alert("Bitte gib eine gültige Menge (mindestens 1) ein.");
                return;
            }

            // Artikel im Objekt speichern/überschreiben
            selectedArtikel[currentArtikelId] = {
                menge: menge,
                notiz: notiz,
                name: currentArtikelName
            };

            // Versteckte Inputs (evtl. alte entfernen, dann neu anlegen)
            removeHiddenInputs(currentArtikelId);

            const container = document.getElementById("selectedArtikel");

            // Neue Inputs anlegen
            const hiddenDiv = document.createElement("div");
            hiddenDiv.setAttribute("data-artikel-id", currentArtikelId);
            hiddenDiv.innerHTML = `
                <input type="hidden" name="artikel[${currentArtikelId}][id]" value="${currentArtikelId}">
                <input type="hidden" name="artikel[${currentArtikelId}][menge]" value="${menge}">
                <input type="hidden" name="artikel[${currentArtikelId}][notiz]" value="${notiz}">
            `;
            container.appendChild(hiddenDiv);

            updateSelectedList();
            closeModal();
        }

        window.onclick = function(event) {
            const modal = document.getElementById("modal");
            if (event.target === modal) {
                closeModal();
            }
        }

        window.onload = function() {
            updateSelectedList();
            // Adresse nur zeigen, wenn Lieferung ausgewählt (beim Seitenreload)
            const typSelect = document.getElementById('typ');
            document.getElementById('adresseGruppe').style.display = (typSelect.value === 'Lieferung') ? 'block' : 'none';
            typSelect.addEventListener('change', function() {
                document.getElementById('adresseGruppe').style.display = (this.value === 'Lieferung') ? 'block' : 'none';
            });
        };
    </script>
</head>
<body>
<div class="container">
      <a href="lieferservice.php" style="display:inline-block; margin-bottom:1rem; color:#0077cc; font-weight:600;">&#8592; Zurück</a>
    <h2>Bestellung: Abholung oder Lieferung</h2>
    <form action="bestellen_lieferung_verarbeiten.php" method="post">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label for="telefon">Telefonnummer:</label>
            <input type="tel" name="telefon" required>
        </div>

        <div class="form-group">
            <label for="typ">Art der Bestellung:</label>
            <select name="typ" id="typ" required>
                <option value="">Bitte wählen</option>
                <option value="Abholung">Abholung</option>
                <option value="Lieferung">Lieferung</option>
            </select>
        </div>

        <div class="form-group" id="adresseGruppe" style="display:none;">
            <label for="adresse">Lieferadresse:</label>
            <input type="text" name="adresse">
        </div>

        <div class="form-group">
            <label for="uhrzeit">Gewünschte Uhrzeit:</label>
            <input type="time" name="uhrzeit" required>
        </div>

        <hr>
        <h3>Speisen auswählen:</h3>
        <div id="selectedArtikel"></div>
        <div id="selectedArtikelList"></div>
        <?php foreach ($artikel as $a): ?>
            <button type="button" class="artikel-button" onclick="openModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['name']) ?>')">
                <?= htmlspecialchars($a['name']) ?> – <?= number_format($a['preis'], 2) ?> €
            </button>
        <?php endforeach; ?>

        <button type="submit">Bestellung abschicken</button>
    </form>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalArtikelName"></h3>
        <label for="mengeInput">Menge:</label>
        <input type="number" id="mengeInput" min="1" value="1" required>
        <label for="notizInput">Notiz:</label>
        <textarea id="notizInput" rows="3" placeholder="z. B. ohne Zwiebeln..."></textarea>
        <br><br>
        <button type="button" onclick="addArtikel()">Hinzufügen</button>
    </div>
</div>
</body>
</html>
