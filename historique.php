<?php 
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit;
}

$currentUserId = $_SESSION['user']['id_utilisateur'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_transactions') {
        // Fetch all transactions for current user (as borrower)
        $stmt = $conn->prepare("SELECT t.*, l.titre, l.auteur, u.nom, u.prenom 
                                FROM transaction_livre t 
                                JOIN livre l ON t.livre_id = l.id_livre 
                                JOIN utilisateur u ON l.proprietaire_id = u.id_utilisateur 
                                WHERE t.emprunteur_id = ?
                                ORDER BY t.date_transaction DESC");
        $stmt->bind_param("i", $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'transactions' => $transactions]);
        exit;
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg">
    <title>Mon Livre - Historique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="enhancements.css">
    <style>
        .nav-link:hover {
            background-color: rgb(235, 235, 235);
            border-radius: 5px;
        }
        .navbar-nav a.active {
            background-color: black;
        }
        header nav {
            box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
        }
        .hover-row:hover {
            background-color: #f8f9fa;
        }
        .badge-lend { background-color: #dbeafe; color: #1e40af; }
        .badge-sell { background-color: #d1fae5; color: #065f46; }
        .badge-exchange { background-color: #e9d5ff; color: #6b21a8; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-pending { background-color: #fef3c7; color: #92400e; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        .filter-tabs {
            display: inline-flex;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 3px;
            gap: 4px;
        }
        .tab-btn {
            padding: 0.5rem 1.25rem;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 20px;
            font-weight: 500;
            color: #6b7280;
        }
        .tab-btn:hover {
            background-color: #f3f4f6;
        }
        .tab-btn.active {
            background-color: white;
            color: black;
        }
        .stats-card {
            border-left: 4px solid #3B4FA1;
        }
        
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
            .filter-tabs {
                width: 100%;
                flex-wrap: wrap;
            }
            .tab-btn {
                flex: 1;
                min-width: 100px;
                font-size: 0.875rem;
            }
            .table {
                font-size: 0.875rem;
            }
            .table th, .table td {
                padding: 0.5rem !important;
            }
            .navbar-nav .nav-link:not(.navbar-brand) {
                font-size: 0;
                padding: 0.5rem !important;
            }
            .navbar-nav .nav-link svg {
                margin: 0 !important;
            }
        }
        
        @media (max-width: 576px) {
            .card {
                padding: 15px !important;
            }
            h2 {
                font-size: 1.5rem !important;
            }
            .table {
                font-size: 0.75rem;
            }
            .badge {
                font-size: 0.7rem !important;
                padding: 0.25rem 0.5rem !important;
            }
            .navbar-nav .nav-link {
                padding: 0.4rem 0.6rem !important;
                margin: 0.2rem !important;
            }
            .ms-auto {
                flex-wrap: wrap !important;
                gap: 0.25rem !important;
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
                        <a class="nav-link d-flex align-items-center ms-lg-2" href="parcourir.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-book me-2" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                            Parcourir
                        </a>
                        <div class="ms-auto d-flex align-items-center gap-2 mt-2 mt-lg-0">
                            <?php if (!isset($_SESSION['user'])): ?>
                            <a class="nav-link" href="connexion.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0z"/><path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Connexion
                            </a>
                            <a class="nav-link text-light border rounded-3 bg-dark ms-2" href="inscrire.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                                S'inscrire
                            </a>
                            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <span class="badge me-3 mt-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.25rem 0.75rem; border-radius: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-shield-check me-1" viewBox="0 0 16 16"><path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/><path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/></svg>
                                Admin
                            </span>
                            <a class="nav-link me-2" href="admin.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/><path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/></svg>
                                Admin Dashboard
                            </a>
                            <a class="nav-link border rounded-3 ms-2" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Déconnexion
                            </a>
                            <?php else: ?>
                            <a class="nav-link d-flex align-items-center me-2" href="dashboard.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layout-wtf" viewBox="0 0 16 16"><path d="M5 1v8H1V1zM1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1zm13 2v5H9V2zM9 1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM5 13v2H3v-2zm-2-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-1v2H9v-2zm-6-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/></svg>
                                <span class="d-none d-lg-inline ms-2">Tableau de bord</span>
                            </a>
                            <a class="nav-link d-flex align-items-center me-2" href="message.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/></svg>
                                <span class="d-none d-lg-inline ms-2">Messages</span>
                            </a>
                            <a class="nav-link d-flex align-items-center me-2 active text-light border rounded-3 bg-dark" href="historique.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/><path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/><path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/></svg>
                                <span class="d-none d-lg-inline ms-2">Historique</span>
                            </a>
                            <a class="nav-link d-flex align-items-center border rounded-3 ms-2" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                <span class="d-none d-lg-inline ms-2">Déconnexion</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <section class="py-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="mb-5">
                <h1 style="color: #3B4FA1;" class="mb-2">Historique des Transactions</h1>
                <p class="text-muted">Consultez l'historique de vos échanges, ventes et prêts de livres</p>
            </div>

            <div class="row mb-5" >
                <div class="col-md-4 mb-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <small class="text-muted">Total des Transactions</small>
                            <h3 id="totalCount" style="color: #3B4FA1;" class="mb-0 mt-1">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card" style="border-left: 4px solid #10b981;">
                        <div class="card-body">
                            <small class="text-muted">Complétées</small>
                            <h3 id="completedCount" class="text-success mb-0 mt-1">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card" style="border-left: 4px solid #f59e0b;">
                        <div class="card-body">
                            <small class="text-muted">En Attente</small>
                            <h3 id="pendingCount" class="mb-0 mt-1" style="color: #f59e0b;">0</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="filter-tabs mb-4" style=background-color:#ecedf1;>
                        <button type="button" class="tab-btn active" data-tab="all">All Transactions</button>
                        <button type="button" class="tab-btn" data-tab="completed">Completed</button>
                        <button type="button" class="tab-btn" data-tab="pending">Pending</button>
                        <button type="button" class="tab-btn" data-tab="cancelled">Cancelled</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Livre</th>
                                    <th>Type</th>
                                    <th>Autre Partie</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <p class="mt-2">Chargement des transactions...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        const defaultTransactions = [
            {
                id: '1',
                bookTitle: 'Introduction aux Algorithmes',
                bookAuthor: 'Thomas H. Cormen',
                type: 'Prêt',
                otherParty: 'Sarah Johnson',
                status: 'Complétée',
                date: '2025-10-15',
                price: null
            },
            {
                id: '2',
                bookTitle: 'Calcul Différentiel et Intégral',
                bookAuthor: 'James Stewart',
                type: 'Vente',
                otherParty: 'Michael Chen',
                status: 'Complétée',
                date: '2025-10-10',
                price: 45
            },
            {
                id: '3',
                bookTitle: 'Chimie Organique',
                bookAuthor: 'Paula Yurkanis Bruice',
                type: 'Échange',
                otherParty: 'Emily Rodriguez',
                status: 'En Attente',
                date: '2025-10-20',
                price: null
            },
            {
                id: '4',
                bookTitle: 'Physique pour Scientifiques',
                bookAuthor: 'Raymond A. Serway',
                type: 'Vente',
                otherParty: 'David Kim',
                status: 'Annulée',
                date: '2025-10-05',
                price: 60
            },
            {
                id: '5',
                bookTitle: 'Concepts de Bases de Données',
                bookAuthor: 'Abraham Silberschatz',
                type: 'Prêt',
                otherParty: 'Lisa Wang',
                status: 'Complétée',
                date: '2025-09-28',
                price: null
            }
        ];

        let transactions = [];
        let currentFilter = 'all';

        async function loadTransactions() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_transactions');
                
                const response = await fetch('historique.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    // Map database transactions to display format
                    transactions = data.transactions.map(t => ({
                        id: t.id_transaction,
                        bookTitle: t.titre,
                        bookAuthor: t.auteur,
                        type: t.type_transact,
                        otherParty: `${t.nom} ${t.prenom}`,
                        status: t.statut === 'en cours' ? 'En Attente' : (t.statut === 'termine' ? 'Complétée' : 'Annulée'),
                        date: t.date_transaction,
                        price: null
                    }));
                } else {
                    // Fallback to default if no transactions
                    transactions = [...defaultTransactions];
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                transactions = [...defaultTransactions];
            }
            
            updateStatistics();
            renderTransactions();
        }

        function updateStatistics() {
            const total = transactions.length;
            const completed = transactions.filter(t => t.status === 'Complétée').length;
            const pending = transactions.filter(t => t.status === 'En Attente').length;

            document.getElementById('totalCount').textContent = total;
            document.getElementById('completedCount').textContent = completed;
            document.getElementById('pendingCount').textContent = pending;
        }

        function getStatusIcon(status) {
            switch (status) {
                case 'Complétée':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle text-success" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/></svg>';
                case 'En Attente':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" style="color: #f59e0b;" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/></svg>';
                case 'Annulée':
                    return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle text-danger" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>';
                default:
                    return '';
            }
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('fr-FR', options);
        }

        function renderTransactions() {
            let filtered = transactions;
            
            if (currentFilter !== 'all') {
                const statusMap = {
                    'completed': 'Complétée',
                    'pending': 'En Attente',
                    'cancelled': 'Annulée'
                };
                filtered = transactions.filter(t => t.status === statusMap[currentFilter]);
            }

            const tbody = document.getElementById('transactionsTableBody');
            
            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-inbox mb-3 opacity-50" viewBox="0 0 16 16"><path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374z"/></svg>
                            <p>Aucune transaction trouvée</p>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filtered.map(transaction => {
                const typeClass = transaction.type === 'Prêt' ? 'badge-lend' : 
                                 transaction.type === 'Vente' ? 'badge-sell' : 'badge-exchange';
                const statusClass = transaction.status === 'Complétée' ? 'badge-completed' :
                                   transaction.status === 'En Attente' ? 'badge-pending' : 'badge-cancelled';
                
                return `
                    <tr class="hover-row">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 40px; height: 56px; flex-shrink: 0;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#999" class="bi bi-book" viewBox="0 0 16 16">
                                        <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                                    </svg>
                                </div>
                                <div>
                                    <div style="color: #3B4FA1; font-weight: 500;">${transaction.bookTitle}</div>
                                    <small class="text-muted">par ${transaction.bookAuthor}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge ${typeClass}">${transaction.type}</span>
                        </td>
                        <td>${transaction.otherParty}</td>
                        <td>${formatDate(transaction.date)}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${getStatusIcon(transaction.status)}
                                <span class="badge ${statusClass}">${transaction.status}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            ${transaction.price ? `${transaction.price}$` : '—'}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.tab;
                renderTransactions();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            loadTransactions();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="enhancements.js"></script>
</body>
</html>
