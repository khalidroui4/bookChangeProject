<?php
session_start();
require_once 'config.php';
// Get only approved books from database
$query = "SELECT l.*, u.nom, u.prenom, u.email 
          FROM livre l 
          JOIN utilisateur u ON l.proprietaire_id = u.id_utilisateur 
          WHERE l.statut = 'disponible'
          ORDER BY l.id_livre DESC";
$result = $conn->query($query);
if (!$result) {
    error_log("SQL Error in parcourir.php: " . $conn->error);
    $livres = [];
} else {
    $livres = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg">
    <title>mon livre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="enhancements.css">
    <link rel="stylesheet" href="features.css">
    
    <style>
        header nav{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
}

.nav-link:hover{
    background-color: rgb(235, 235, 235);
    border-radius: 5px;
}
.search-bar{
  display: block;
  width: 900px;
  padding: 0.375rem 0.75rem; 
  line-height: 1.5;
  background-clip: padding-box;
  border: 1px solid transparent;
  border-radius: 0.375rem; 
  background-color: #f3f2f4;
}
.search-bar:focus{
  outline: 0;
}
#bookGrid .card:hover{
  box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
}

.dropdown-menu {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 0.5rem 0;
    min-width: 250px;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    color: #374151;
    transition: background-color 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dropdown-item:hover {
    background-color: #f3f4f6;
    color: #111827;
}

.dropdown-item.active {
    background-color: #f9fafb;
    color: #111827;
    font-weight: 500;
}

.dropdown-item.active::after {
    content: "✓";
    color: #3B4FA1;
    font-weight: bold;
    margin-left: auto;
}

@media (max-width: 992px) {
    .search-bar {
        width: 100% !important;
        max-width: 600px;
    }
}

@media (max-width: 768px) {
    .search-bar {
        width: 100% !important;
        max-width: 100%;
    }
    #bookGrid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 576px) {
    #bookGrid {
        grid-template-columns: 1fr !important;
    }
    .card {
        font-size: 0.875rem;
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/><path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/></svg>
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
    <section class="p-5" style="background-color: #f9fafb;">
        <div class="container">
        <h4 style="color: #3B4FA1;">Parcourir les Livres</h4>
        <p>Trouvez des manuels partagés par des étudiants de votre université</p>

        <div class="d-flex align-items-center">
            <div class="d-flex align-items-center justify-content-center px-2 rounded-3" style="background-color: #f3f2f4;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                <input type="search" id="searchInput" name="q" placeholder="Rechercher par titre..." class="search-bar" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" />

            </div>
            <button
                class="ms-2 btn btn-light rounded-3"
                style="border-color:#f3f2f4;background-color:#fff;"
                data-bs-toggle="offcanvas"
                data-bs-target="#filterPanel"
                >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filter-right"><path d="M14 10.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0 0 1h3a.5.5 0 0 0 .5-.5m0-3a.5.5 0 0 0-.5-.5h-7a.5.5 0 0 0 0 1h7a.5.5 0 0 0 .5-.5m0-3a.5.5 0 0 0-.5-.5h-11a.5.5 0 0 0 0 1h11a.5.5 0 0 0 .5-.5"/></svg>
                Filtres
            </button>
        </div>

        <div class="btn-grp mt-3 d-flex flex-wrap gap-2">
            <button id="mainCatBtn" class="btn" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">
                Toutes les catégories
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="ms-2" viewBox="0 0 16 16">
                    <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a.859.859 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                </svg>
            </button>
            <ul class="dropdown-menu" id="mainCatMenu">
                <li><a href="#" class="dropdown-item active" data-value="all">Toutes les catégories</a></li>
                <li><a href="#" class="dropdown-item" data-value="Informatique">Informatique</a></li>
                <li><a href="#" class="dropdown-item" data-value="Mathématiques">Mathématiques</a></li>
                <li><a href="#" class="dropdown-item" data-value="Physique">Physique</a></li>
                <li><a href="#" class="dropdown-item" data-value="Chimie">Chimie</a></li>
                <li><a href="#" class="dropdown-item" data-value="Commerce">Commerce</a></li>

            </ul>

            <button id="mainTypeBtn" class="btn" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">
                Tous les types
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="ms-2" viewBox="0 0 16 16">
                    <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a.859.859 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                </svg>
            </button>
            <ul class="dropdown-menu" id="mainTypeMenu">
                <li><a href="#" class="dropdown-item active" data-value="all">Tous les types</a></li>
                <li><a href="#" class="dropdown-item" data-value="Prêt">Prêt</a></li>
                <li><a href="#" class="dropdown-item" data-value="Vente">Vente</a></li>
                <li><a href="#" class="dropdown-item" data-value="Échange">Échange</a></li>
            </ul>

            <button id="mainEtatBtn" class="btn" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">
                Tous les états
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="ms-2" viewBox="0 0 16 16">
                    <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a.859.859 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                </svg>
            </button>
            <ul class="dropdown-menu" id="mainEtatMenu">
                <li><a href="#" class="dropdown-item active" data-value="all">Tous les états</a></li>
                <li><a href="#" class="dropdown-item" data-value="Excellent">Excellent</a></li>
                <li><a href="#" class="dropdown-item" data-value="Bon">Bon</a></li>
                <li><a href="#" class="dropdown-item" data-value="Correct">Correct</a></li>
            </ul>
        </div>
        </div>
    

        <div class="offcanvas offcanvas-end" tabindex="-1" id="filterPanel">
            <div class="offcanvas-header">
                <h5>Filtrer les Livres</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <p class="text-muted">Affinez votre recherche avec ces filtres</p>

                <div class="mb-3">
                    <label>Catégorie</label>
                    <button id="sideCatBtn" class="btn dropdown-toggle w-100 text-start" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">Toutes les catégories</button>
                    <ul class="dropdown-menu" id="sideCatMenu">
                    <li><a href="#" class="dropdown-item active" data-value="all">Toutes les catégories</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Informatique">Informatique</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Mathématiques">Mathématiques</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Physique">Physique</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Chimie">Chimie</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Commerce">Commerce</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Psychologie">Psychologie</a></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label>Type</label>
                    <button id="sideTypeBtn" class="btn dropdown-toggle w-100 text-start" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">Tous les types</button>
                    <ul class="dropdown-menu" id="sideTypeMenu">
                    <li><a href="#" class="dropdown-item active" data-value="all">Tous les types</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Prêt">Prêt</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Vente">Vente</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Échange">Échange</a></li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label>État</label>
                    <button id="sideEtatBtn" class="btn dropdown-toggle w-100 text-start" data-bs-toggle="dropdown" style="background-color:#f3f2f4;">Tous les états</button>
                    <ul class="dropdown-menu" id="sideEtatMenu">
                    <li><a href="#" class="dropdown-item active" data-value="all">Tous les états</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Excellent">Excellent</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Bon">Bon</a></li>
                    <li><a href="#" class="dropdown-item" data-value="Correct">Correct</a></li>
                    </ul>
                </div>

                <button id="resetFilters" class="btn btn-outline-secondary w-100 mt-3">Réinitialiser les filtres</button>
            </div>
        </div>
        <p id="bookCount" class="mt-3" style="margin-left: 70px ;"></p>
        <article class="p-5">
            <div class="container">
                <div id="bookGrid" class="row g-4">
                    <?php if (empty($livres)): ?>
                        <div class="col-12 text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-inbox mb-3 opacity-50" viewBox="0 0 16 16"><path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374z"/></svg>
                            <p class="text-muted">Aucun livre disponible pour le moment</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($livres as $livre): 
                            // Badge colors based on type
                            $typeBadge = '';
                            if ($livre['type'] === 'Prêt') {
                                $typeBadge = 'style="color:#193db9 ; background-color: #dbeafe;"';
                            } elseif ($livre['type'] === 'Vente') {
                                $typeBadge = 'style="color:#58a37b ;background-color: #dbfde6;"';
                            } else {
                                $typeBadge = 'style="color: #7e2cbb;background-color:#f2e8fe;"';
                            }
                            
                            // Badge colors based on etat
                            $etatBadge = '';
                            if ($livre['etat'] === 'Excellent') {
                                $etatBadge = 'style="color:#58a37b ;background-color: #dbfde6;"';
                            } elseif ($livre['etat'] === 'Bon') {
                                $etatBadge = 'style="color:#193db9 ; background-color: #dbeafe;"';
                            } else {
                                $etatBadge = 'style="color:#973d01 ;background-color: #fff3c7;"';
                            }
                        ?>
                        <div class="col-md-4 book-card" data-category="<?php echo htmlspecialchars($livre['categorie']); ?>" data-type="<?php echo htmlspecialchars($livre['type']); ?>" data-etat="<?php echo htmlspecialchars($livre['etat']); ?>">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($livre['image']); ?>" class="card-img-top" style="height: 460px; object-fit: cover;" alt="<?php echo htmlspecialchars($livre['titre']); ?>">
                                <div class="card-body">
                                    <span class="badge" <?php echo $typeBadge; ?>><?php echo htmlspecialchars($livre['type']); ?></span>
                                    <span class="badge" <?php echo $etatBadge; ?>><?php echo htmlspecialchars($livre['etat']); ?></span>
                                    <h5 class="card-title mt-2" style="color: #3B4FA1;"><?php echo htmlspecialchars($livre['titre']); ?></h5>
                                    <p class="card-text" style="color: #4a5464;">par <?php echo htmlspecialchars($livre['auteur']); ?></p>
                                    <span class="badge border border-secondary border-1 rounded-3 text-dark"><?php echo htmlspecialchars($livre['categorie']); ?></span>
                                    <?php if ($livre['type'] === 'Vente' && !empty($livre['prix'])): ?>
                                    <span style="color: #58a37b;position: absolute; right: 0; margin-right: 20px;"><?php echo htmlspecialchars($livre['prix']); ?>$</span>
                                    <?php endif; ?>
                                    <a href="details.php?id=<?php echo $livre['id_livre']; ?>" class="btn btn-dark w-100 mt-5">
                                        Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
    </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <script src="enhancements.js"></script>
  <script src="features.js"></script>
  <script>
  const filterPairs = [
    { mainBtn: "mainCatBtn", mainMenu: "mainCatMenu", sideBtn: "sideCatBtn", sideMenu: "sideCatMenu", defaultText: "Toutes les catégories" },
    { mainBtn: "mainTypeBtn", mainMenu: "mainTypeMenu", sideBtn: "sideTypeBtn", sideMenu: "sideTypeMenu", defaultText: "Tous les types" },
    { mainBtn: "mainEtatBtn", mainMenu: "mainEtatMenu", sideBtn: "sideEtatBtn", sideMenu: "sideEtatMenu", defaultText: "Tous les états" },
  ];

  function updateDropdown(btn, menu, value, text) {
    btn.textContent = text;
    menu.querySelectorAll(".dropdown-item").forEach(i => i.classList.remove("active"));
    const match = menu.querySelector(`[data-value="${value}"]`);
    if (match) match.classList.add("active");
  }

  filterPairs.forEach(pair => {
    const mainBtn = document.getElementById(pair.mainBtn);
    const sideBtn = document.getElementById(pair.sideBtn);
    const mainMenu = document.getElementById(pair.mainMenu);
    const sideMenu = document.getElementById(pair.sideMenu);

    [mainMenu, sideMenu].forEach(menu => {
      menu.addEventListener("click", e => {
        e.preventDefault();
        const item = e.target.closest(".dropdown-item");
        if (!item) return;
        const value = item.dataset.value;
        const text = item.textContent.trim();

        updateDropdown(mainBtn, mainMenu, value, text);
        updateDropdown(sideBtn, sideMenu, value, text);

        applyFilters();
      });
    });
  });

  document.getElementById("resetFilters").addEventListener("click", () => {
    filterPairs.forEach(pair => {
      const mainBtn = document.getElementById(pair.mainBtn);
      const sideBtn = document.getElementById(pair.sideBtn);
      const mainMenu = document.getElementById(pair.mainMenu);
      const sideMenu = document.getElementById(pair.sideMenu);
      updateDropdown(mainBtn, mainMenu, "all", pair.defaultText);
      updateDropdown(sideBtn, sideMenu, "all", pair.defaultText);
    });
    applyFilters();
  });

  function applyFilters() {
    const category = document.querySelector("#mainCatMenu .active").dataset.value;
    const type = document.querySelector("#mainTypeMenu .active").dataset.value;
    const etat = document.querySelector("#mainEtatMenu .active").dataset.value;
    const searchInput = document.getElementById("searchInput");
    const searchTerm = (searchInput ? searchInput.value : "").trim().toLowerCase();

    let visibleCount = 0;
    document.querySelectorAll(".book-card").forEach(card => {
      const matchesCategory = category === "all" || card.dataset.category === category;
      const matchesType = type === "all" || card.dataset.type === type;
      const matchesEtat = etat === "all" || card.dataset.etat === etat;
      const title = card.querySelector(".card-title") ? card.querySelector(".card-title").textContent.toLowerCase() : "";
      const author = card.querySelector(".card-text") ? card.querySelector(".card-text").textContent.toLowerCase() : "";
      const combined = [title, author, (card.dataset.category||"").toLowerCase(), (card.dataset.type||"").toLowerCase(), (card.dataset.etat||"").toLowerCase()].join(" ");
      const matchesText = searchTerm === "" || combined.includes(searchTerm);
      const visible = matchesCategory && matchesType && matchesEtat && matchesText;
      card.style.display = visible ? "block" : "none";
      if (visible) visibleCount++;
    });

    const countParagraph = document.getElementById("bookCount");
    if (countParagraph) {
      countParagraph.textContent = visibleCount === 0
        ? "Aucun livre trouvé"
        : `Affichage de ${visibleCount} livre${visibleCount > 1 ? "s" : ""}`;
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchInput");
    if (searchInput) {
      searchInput.addEventListener("input", applyFilters);
    }
    applyFilters();
  });
</script>


</body>
</html>
