<?php
session_start();

// ==========================================
// BASE DE DONNÉES (SQLite, créée automatiquement)
// ==========================================
$dbFile = __DIR__ . '/restaurant.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE IF NOT EXISTS clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        postnom TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        date_inscription TEXT NOT NULL
    )
");

// ==========================================
// CONFIGURATION ADMIN
// ==========================================
define('ADMIN_MDP', 'admin123'); // change ce mot de passe

$erreur = '';
$succes = '';

// --- Connexion admin ---
if (isset($_POST['action']) && $_POST['action'] === 'connexion_admin') {
    if (($_POST['mot_de_passe'] ?? '') === ADMIN_MDP) {
        $_SESSION['admin'] = true;
    } else {
        $erreur = "Mot de passe administrateur incorrect.";
    }
}

// --- Déconnexion admin ---
if (isset($_GET['deconnexion'])) {
    unset($_SESSION['admin']);
    header("Location: " . basename(__FILE__));
    exit;
}

// --- Suppression d'un client (admin uniquement) ---
if (isset($_GET['supprimer']) && !empty($_SESSION['admin'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([(int) $_GET['supprimer']]);
    header("Location: " . basename(__FILE__) . "?admin=1");
    exit;
}

// --- Inscription d'un client (formulaire public) ---
if (isset($_POST['action']) && $_POST['action'] === 'inscription') {
    $nom     = trim($_POST['nom'] ?? '');
    $postnom = trim($_POST['postnom'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    if ($nom === '' || $postnom === '' || $email === '') {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO clients (nom, postnom, email, date_inscription) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $postnom, $email, date('Y-m-d H:i:s')]);
            $succes = "Merci $nom, vos coordonnées ont bien été enregistrées !";
        } catch (PDOException $e) {
            $erreur = ($e->getCode() == 23000) ? "Cet email est déjà enregistré." : "Erreur : " . $e->getMessage();
        }
    }
}

$modeAdmin = !empty($_SESSION['admin']) && isset($_GET['admin']);
$clients = $modeAdmin ? $pdo->query("SELECT * FROM clients ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Le Jardin Doré — Restaurant</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Georgia', 'Times New Roman', serif;
        background: #1a1410;
        color: #f5e9d3;
        line-height: 1.6;
    }
    header {
        background: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)),
                    url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200') center/cover;
        padding: 90px 20px 70px;
        text-align: center;
        border-bottom: 4px solid #c9a24b;
    }
    header h1 {
        font-size: 42px;
        letter-spacing: 2px;
        color: #f5e9d3;
    }
    header p {
        margin-top: 10px;
        font-style: italic;
        color: #c9a24b;
        font-size: 17px;
    }
    nav {
        text-align: center;
        padding: 14px;
        background: #241b13;
        border-bottom: 1px solid #c9a24b55;
    }
    nav a {
        color: #c9a24b;
        text-decoration: none;
        margin: 0 14px;
        font-size: 14px;
        letter-spacing: 1px;
    }
    nav a:hover { text-decoration: underline; }

    main { max-width: 720px; margin: 0 auto; padding: 50px 20px; }

    .section-titre {
        text-align: center;
        font-size: 26px;
        color: #c9a24b;
        margin-bottom: 30px;
        letter-spacing: 1px;
    }

    .plats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 60px;
    }
    .plat {
        background: #241b13;
        border: 1px solid #c9a24b33;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    .plat h3 { color: #f5e9d3; font-size: 17px; margin-bottom: 6px; }
    .plat span { color: #c9a24b; font-weight: bold; }
    .plat p { font-size: 13px; color: #d8c8ab; margin-top: 6px; }

    .carte {
        background: #241b13;
        border: 1px solid #c9a24b55;
        border-radius: 10px;
        padding: 30px;
    }
    label { display: block; margin-top: 14px; font-size: 14px; color: #c9a24b; }
    input {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        background: #1a1410;
        border: 1px solid #c9a24b55;
        border-radius: 5px;
        color: #f5e9d3;
        font-family: inherit;
    }
    button {
        margin-top: 20px;
        width: 100%;
        padding: 12px;
        background: #c9a24b;
        color: #1a1410;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        letter-spacing: 1px;
        cursor: pointer;
    }
    button:hover { background: #e0b95c; }

    .erreur { background: #4a1c1c; color: #f5b8b8; padding: 12px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
    .succes { background: #1c4a2a; color: #b8f5c8; padding: 12px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { text-align: left; padding: 10px; border-bottom: 1px solid #c9a24b33; font-size: 14px; }
    th { color: #c9a24b; }
    a.suppr { color: #e07a7a; text-decoration: none; font-size: 13px; }
    a.deco { color: #c9a24b; text-decoration: none; font-size: 13px; float: right; }

    footer { text-align: center; padding: 25px; font-size: 12px; color: #7a6a54; }
</style>
</head>
<body>

<?php if ($modeAdmin): ?>

    <!-- ================= ESPACE ADMIN ================= -->
    <header>
        <h1>Espace Administrateur</h1>
        <p>Le Jardin Doré</p>
    </header>
    <nav>
        <a href="?">← Retour au site</a>
        <a href="?deconnexion=1">Déconnexion admin</a>
    </nav>
    <main>
        <?php if ($succes): ?><div class="succes"><?= htmlspecialchars($succes) ?></div><?php endif; ?>

        <div class="section-titre">Clients enregistrés (<?= count($clients) ?>)</div>
        <div class="carte">
            <table>
                <tr><th>Nom</th><th>Postnom</th><th>Email</th><th>Date</th><th></th></tr>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nom']) ?></td>
                    <td><?= htmlspecialchars($c['postnom']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['date_inscription']) ?></td>
                    <td><a class="suppr" href="?admin=1&supprimer=<?= $c['id'] ?>" onclick="return confirm('Supprimer ce client ?');">Supprimer</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clients)): ?>
                <tr><td colspan="5" style="text-align:center; color:#7a6a54;">Aucun client enregistré.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>

<?php elseif (isset($_GET['admin'])): ?>

    <!-- ================= FORMULAIRE DE CONNEXION ADMIN ================= -->
    <header>
        <h1>Le Jardin Doré</h1>
        <p>Espace administrateur</p>
    </header>
    <main>
        <?php if ($erreur): ?><div class="erreur"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <div class="carte">
            <form method="POST" action="?admin=1">
                <input type="hidden" name="action" value="connexion_admin">
                <label>Mot de passe administrateur</label>
                <input type="password" name="mot_de_passe" required autofocus>
                <button type="submit">Se connecter</button>
            </form>
        </div>
    </main>

<?php else: ?>

    <!-- ================= SITE PUBLIC ================= -->
    <header>
        <h1>Le Jardin Doré</h1>
        <p>Cuisine raffinée au cœur de Bujumbura</p>
    </header>
    <nav>
        <a href="#menu">Notre carte</a>
        <a href="#reservation">Réservation</a>
        <a href="?admin=1">Espace admin</a>
    </nav>

    <main>
        <div class="section-titre" id="menu">Notre carte</div>
        <div class="plats">
            <div class="plat">
                <h3>Poulet à la brochette</h3>
                <p>Mariné aux épices locales, servi avec riz</p>
                <span>12 000 BIF</span>
            </div>
            <div class="plat">
                <h3>Tilapia grillé</h3>
                <p>Poisson frais du lac Tanganyika</p>
                <span>15 000 BIF</span>
            </div>
            <div class="plat">
                <h3>Brochettes de bœuf</h3>
                <p>Accompagnées de légumes sautés</p>
                <span>13 500 BIF</span>
            </div>
            <div class="plat">
                <h3>Salade tropicale</h3>
                <p>Fruits et légumes de saison</p>
                <span>7 000 BIF</span>
            </div>
        </div>

        <div class="section-titre" id="reservation">Laissez-nous vos coordonnées</div>
        <?php if ($erreur): ?><div class="erreur"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <?php if ($succes): ?><div class="succes"><?= htmlspecialchars($succes) ?></div><?php endif; ?>
        <div class="carte">
            <form method="POST">
                <input type="hidden" name="action" value="inscription">
                <label>Nom</label>
                <input type="text" name="nom" required>
                <label>Postnom</label>
                <input type="text" name="postnom" required>
                <label>Email</label>
                <input type="email" name="email" required>
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </main>

<?php endif; ?>

<footer>&copy; <?= date('Y') ?> Le Jardin Doré — Tous droits réservés</footer>
</body>
</html>
