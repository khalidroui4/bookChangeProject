<?php
session_start();
require_once 'config.php';

$bookId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$book = null;

if ($bookId > 0) {
    // Fetch book with owner information
    $stmt = $conn->prepare("SELECT l.*, u.nom, u.prenom, u.email, u.id_utilisateur 
                            FROM livre l 
                            JOIN utilisateur u ON l.proprietaire_id = u.id_utilisateur 
                            WHERE l.id_livre = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
    
    // Count how many books this owner has listed
    if ($book) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM livre WHERE proprietaire_id = ?");
        $stmt->bind_param("i", $book['proprietaire_id']);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $countData = $countResult->fetch_assoc();
        $book['proprietaire_livres_count'] = $countData['count'];
        $stmt->close();
    }
}

// Redirect if book not found
if (!$book) {
    header('Location: parcourir.php');
    exit;
}

// Format book data for display
$livre = [
    'titre' => $book['titre'],
    'auteur' => $book['auteur'],
    'categorie' => $book['categorie'],
    'type' => $book['type'],
    'etat' => $book['etat'],
    'prix' => ($book['type'] === 'Vente' && !empty($book['prix'])) ? $book['prix'] . '$' : '—',
    'image' => $book['image'],
    'description' => $book['description'] ?? 'Aucune description disponible.',
    'isbn' => $book['isbn'] ?? '—',
    'edition' => $book['edition'] ?? '—',
    'annee' => $book['annee'] ?? '—',
    'pages' => $book['pages'] ?? '—',
    'proprietaire' => [
        'id' => $book['proprietaire_id'],
        'nom' => $book['nom'] . ' ' . $book['prenom'],
        'email' => $book['email'],
        'livres_listes' => $book['proprietaire_livres_count'] ?? 0,
        'note' => '⭐ 4.8'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Livre</title>
    <link rel="icon" href="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="animations.css?v=2.0">
    <link rel="stylesheet" href="enhancements.css?v=2.0">
    <link rel="stylesheet" href="features.css?v=2.0">
    <style>
        header nav{
            box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
        }

        .nav-link:hover{
            background-color: rgb(235, 235, 235);
            border-radius: 5px;
        }
        .container .btn:hover{
            background-color: rgb(235, 235, 235);
            border-radius: 5px;
        }
        
        .col-md-5 img {
            height: auto;
            max-height: 700px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .badge.type-pret{
            background-color: #dbeafe;
            color: #193db9;
        }
        .badge.type-vente{
            background-color: #dbfde6;
            color: #58a37b;
        }
        .badge.type-echange{
            background-color: #f2e8fe;
            color: #7e2cbb;
        }
        .badge.etat-excellent{
            background-color: #dbfde6;
            color: #58a37b;
        }
        .badge.etat-bon{
            background-color: #dbeafe;
            color: #193db9;
        }
        .badge.etat-correct{
            background-color: #fff3c7;
            color: #973d01;
        }
        @media (max-width: 768px){
            .col-md-5 img {
                max-height: 400px;
            }
        }
    </style>
</head>
<body>
    <header class="sticky-top bg-light">
        <nav class="navbar navbar-expand-lg" style="background-color:#fefeff;">
            <div class="container-fluid p-2">
                <img src="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg" alt="logo" style="width: 50px;" class="d-none d-md-block">
                <a class="navbar-brand ms-2" href="index.php">Mon Livre</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav w-100">
                        <a class="nav-link ms-lg-5 d-flex align-items-center" aria-current="page" href="index.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-house-door me-2" viewBox="0 0 16 16"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z"/></svg>
                            Accueil
                        </a>
                        <a class="nav-link d-flex align-items-center ms-lg-2 active text-light border rounded-3 bg-dark" href="parcourir.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-book me-2" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                            Parcourir
                        </a>
                        <div class="ms-auto d-flex align-items-center gap-2 mt-2 mt-lg-0">
                            <?php if (!isset($_SESSION['user'])): ?>
                            <a class="nav-link" href="connexion.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0z"/><path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Connexion
                            </a>
                            <a class="nav-link text-light border rounded-3 bg-dark ms-2" href="inscrire.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                                S'inscrire
                            </a>
                            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <a class="nav-link d-flex align-items-center" href="admin.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check me-2" viewBox="0 0 16 16">
                                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                                    <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                                </svg>
                                Admin
                            </a>
                            <a class="nav-link border rounded-3 ms-2 d-flex align-items-center" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Déconnexion
                            </a>
                            <?php else: ?>
                            <a class="nav-link d-flex me-2" href="dashboard.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" fill="currentColor" class="bi bi-layout-wtf mt-1 me-1" viewBox="0 0 16 16"><path d="M5 1v8H1V1zM1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1zm13 2v5H9V2zM9 1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM5 13v2H3v-2zm-2-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-1v2H9v-2zm-6-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/></svg>
                                Tableau de bord
                            </a>
                            <a class="nav-link me-2" href="message.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/></svg>
                                Messages
                            </a>
                            <a class="nav-link me-2" href="historique.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.870a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/><path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/></svg>
                                Historique
                            </a>
                            <a class="nav-link border rounded-3 ms-2" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Déconnexion
                            </a>
                            <?php endif; ?>
                        </div>
                    </div> 
                </div>
            </div>
        </nav>
    </header>

    <section class="container py-5">
        <div class="row g-5">
            <div class="col-md-5">
                <img src="<?php echo htmlspecialchars($livre['image']); ?>" alt="<?php echo htmlspecialchars($livre['titre']); ?>" class="img-fluid w-100">
            </div>
            <div class="col-md-7">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge type-<?php echo strtolower($livre['type']); ?>"><?php echo htmlspecialchars($livre['type']); ?></span>
                    <span class="badge etat-<?php echo strtolower($livre['etat']); ?>"><?php echo htmlspecialchars($livre['etat']); ?></span>
                    <?php if ($livre['type'] === 'Vente' && $livre['prix'] !== '—'): ?>
                    <span class="badge bg-success"><?php echo htmlspecialchars($livre['prix']); ?></span>
                    <?php endif; ?>
                </div>
                
                <h1 class="mb-3" style="color: #3B4FA1;"><?php echo htmlspecialchars($livre['titre']); ?></h1>
                <p class="lead text-muted">par <?php echo htmlspecialchars($livre['auteur']); ?></p>
                
                <div class="mb-4">
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($livre['categorie']); ?></span>
                </div>
                
                <h5 class="mb-3">Description</h5>
                <p><?php echo nl2br(htmlspecialchars($livre['description'])); ?></p>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Détails du Livre</h5>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <strong>ISBN:</strong> <?php echo htmlspecialchars($livre['isbn']); ?>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Édition:</strong> <?php echo htmlspecialchars($livre['edition']); ?>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Année:</strong> <?php echo htmlspecialchars($livre['annee']); ?>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Pages:</strong> <?php echo htmlspecialchars($livre['pages']); ?>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3">Propriétaire</h5>
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($livre['proprietaire']['nom']); ?></h6>
                        <p class="card-text text-muted mb-2"><?php echo htmlspecialchars($livre['proprietaire']['email']); ?></p>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($livre['proprietaire']['livres_listes']); ?> livre(s) listé(s) • <?php echo htmlspecialchars($livre['proprietaire']['note']); ?>
                        </small>
                    </div>
                </div>
                
                <div class="mt-4 d-flex gap-2">
                    <?php 
                    if (isset($_SESSION['user']) && isset($livre['proprietaire']['id'])) {
                        $messageUrl = "message.php?owner=" . intval($livre['proprietaire']['id']);
                    } elseif (isset($_SESSION['user'])) {
                        $messageUrl = "message.php?owner=" . urlencode($livre['proprietaire']['nom']) . "&email=" . urlencode($livre['proprietaire']['email']);
                    } else {
                        $messageUrl = "connexion.php";
                    }
                    ?>
                    <a href="<?php echo $messageUrl; ?>" class="btn btn-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left me-2" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/></svg>
                        Message
                    </a>
                    <a href="parcourir.php" class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="enhancements.js"></script>
</body>
</html>
