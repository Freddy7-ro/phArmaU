<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'pharmaurgence_db';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connexion impossible à la base de données : ' . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$message = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$search = trim($_GET['search'] ?? '');
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editData = null;

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '0');
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name !== '' && $category !== '') {
        $stmt = $conn->prepare('INSERT INTO medicines (name, category, price, stock, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('ssdis', $name, $category, $price, $stock, $description);
        $stmt->execute();
        $message = 'Médicament ajouté avec succès.';
    } else {
        $message = 'Le nom et la catégorie sont obligatoires.';
    }
} elseif ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '0');
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($id > 0 && $name !== '' && $category !== '') {
        $stmt = $conn->prepare('UPDATE medicines SET name=?, category=?, price=?, stock=?, description=? WHERE id=?');
        $stmt->bind_param('ssdisi', $name, $category, $price, $stock, $description, $id);
        $stmt->execute();
        $message = 'Médicament modifié avec succès.';
    } else {
        $message = 'Impossible de modifier ce médicament.';
    }
} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM medicines WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $message = 'Médicament supprimé.';
    }
}

if ($editId > 0) {
    $stmt = $conn->prepare('SELECT * FROM medicines WHERE id=?');
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

$sql = 'SELECT * FROM medicines';
$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' WHERE name LIKE ? OR category LIKE ? OR description LIKE ?';
    $like = '%' . $search . '%';
    $types = 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= ' ORDER BY name ASC';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$medicines = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion des médicaments</title>
    <link rel="stylesheet" href="pharmacie.css?v=3" />
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a class="brand" href="index.html">
                <span class="brand-icon">✚</span>
                <span>PharmaU</span>
            </a>
            <nav class="nav">
                <a href="index.html">Accueil</a>
                <a href="view_orders.php">Commandes</a>
                <a href="medicaments.php">Médicaments</a>
            </nav>
            <a class="btn btn-primary" href="index.html#commande">Commander</a>
        </div>
    </header>

    <main class="container">
        <section class="section">
            <div class="section-header">
                <span>Gestion pharmaceutique</span>
                <h2>Ajouter, modifier, supprimer et rechercher</h2>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form class="panel" method="get" action="medicaments.php">
                <label for="search">Rechercher un médicament</label>
                <div class="inline-form">
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, catégorie ou description" />
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>

            <form class="panel" method="post" action="medicaments.php">
                <input type="hidden" name="action" value="<?= $editData ? 'edit' : 'add' ?>" />
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>" />
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label for="name">Nom du médicament</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($editData['name'] ?? '') ?>" required />
                    </div>
                    <div>
                        <label for="category">Catégorie</label>
                        <input type="text" id="category" name="category" value="<?= htmlspecialchars($editData['category'] ?? '') ?>" required />
                    </div>
                    <div>
                        <label for="price">Prix (FCFA)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?= htmlspecialchars($editData['price'] ?? '0') ?>" required />
                    </div>
                    <div>
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($editData['stock'] ?? '0') ?>" required />
                    </div>
                </div>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Description, dosage ou remarques"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>

                <div class="inline-form">
                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Enregistrer les modifications' : 'Ajouter le médicament' ?></button>
                    <?php if ($editData): ?>
                        <a class="btn btn-secondary" href="medicaments.php">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="panel">
                <h3>Liste des médicaments</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($medicines)): ?>
                            <tr><td colspan="5">Aucun médicament trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($medicines as $medicine): ?>
                                <tr>
                                    <td><?= htmlspecialchars($medicine['name']) ?></td>
                                    <td><?= htmlspecialchars($medicine['category']) ?></td>
                                    <td><?= htmlspecialchars($medicine['price']) ?> FCFA</td>
                                    <td><?= htmlspecialchars($medicine['stock']) ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="medicaments.php?edit_id=<?= (int)$medicine['id'] ?>">Modifier</a>
                                            <form method="post" action="medicaments.php" class="inline-delete">
                                                <input type="hidden" name="action" value="delete" />
                                                <input type="hidden" name="id" value="<?= (int)$medicine['id'] ?>" />
                                                <button type="submit" onclick="return confirm('Supprimer ce médicament ?')">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer-grid">
            <div>
                <h3>PharmaUrgence</h3>
                <p>Gestion simple et rapide des médicaments.</p>
            </div>
            <div>
                <h4>Navigation</h4>
                <p><a href="index.html" style="color: white;">Accueil</a></p>
                <p><a href="view_orders.php" style="color: white;">Commandes</a></p>
            </div>
            <div>
                <h4>Note</h4>
                <p>Cette gestion est prête pour un premier usage local.</p>
            </div>
        </div>
    </footer>
</body>
</html>
