<?php 
session_start();
require_once 'config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: connexion.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_stats') {
        // Fetch statistics
        $stats = [];
        
        // Total users
        $result = $conn->query("SELECT COUNT(*) as count FROM utilisateur");
        $stats['totalUsers'] = $result->fetch_assoc()['count'];
        
        // Total books
        $result = $conn->query("SELECT COUNT(*) as count FROM livre");
        $stats['totalBooks'] = $result->fetch_assoc()['count'];
        
        // Active users (users with at least one book or transaction)
        $result = $conn->query("SELECT COUNT(DISTINCT u.id_utilisateur) as count FROM utilisateur u 
                               LEFT JOIN livre l ON u.id_utilisateur = l.proprietaire_id 
                               LEFT JOIN transaction_livre t ON u.id_utilisateur = t.emprunteur_id 
                               WHERE l.id_livre IS NOT NULL OR t.id_transaction IS NOT NULL");
        $stats['activeUsers'] = $result->fetch_assoc()['count'];
        
        // Total transactions
        $result = $conn->query("SELECT COUNT(*) as count FROM transaction_livre");
        $stats['totalTransactions'] = $result->fetch_assoc()['count'];
        
        // Pending moderation (books with statut en_attente)
        $result = $conn->query("SELECT COUNT(*) as count FROM livre WHERE statut = 'en_attente'");
        $stats['pendingModeration'] = $result->fetch_assoc()['count'];
        
        // Revenue (from completed sale transactions - note: prix should be added to transaction table)
        $stats['revenue'] = 0; // TODO: implement when prix is in transaction table
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }
    
    if ($_POST['action'] === 'get_users') {
        $stmt = $conn->prepare("SELECT u.id_utilisateur, u.nom, u.prenom, u.email, u.role, u.date_inscription, u.statut,
                                (SELECT COUNT(*) FROM livre WHERE proprietaire_id = u.id_utilisateur) as books_count
                                FROM utilisateur u
                                ORDER BY u.date_inscription DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }
    
    if ($_POST['action'] === 'get_books') {
        // Only show pending books for moderation
        $stmt = $conn->prepare("SELECT l.*, u.nom, u.prenom, u.email 
                                FROM livre l 
                                JOIN utilisateur u ON l.proprietaire_id = u.id_utilisateur 
                                WHERE l.statut = 'en_attente'
                                ORDER BY l.id_livre DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $books = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'books' => $books]);
        exit;
    }
    
    if ($_POST['action'] === 'approve_book') {
        $bookId = intval($_POST['book_id']);
        $stmt = $conn->prepare("UPDATE livre SET statut = 'disponible' WHERE id_livre = ?");
        $stmt->bind_param("i", $bookId);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($_POST['action'] === 'reject_book') {
        $bookId = intval($_POST['book_id']);
        $stmt = $conn->prepare("DELETE FROM livre WHERE id_livre = ?");
        $stmt->bind_param("i", $bookId);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($_POST['action'] === 'get_all_books') {
        // Get all books (approved and pending)
        $stmt = $conn->prepare("SELECT l.*, u.nom, u.prenom, u.email 
                                FROM livre l 
                                JOIN utilisateur u ON l.proprietaire_id = u.id_utilisateur 
                                ORDER BY l.id_livre DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        $books = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'books' => $books]);
        exit;
    }
    
    if ($_POST['action'] === 'delete_book') {
        $bookId = intval($_POST['book_id']);
        $stmt = $conn->prepare("DELETE FROM livre WHERE id_livre = ?");
        $stmt->bind_param("i", $bookId);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg">
    <title>Mon Livre - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="enhancements.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .nav-link:hover {
            background-color: rgb(235, 235, 235);
            border-radius: 5px;
        }
        .navbar-nav a.active {
            background-color: black;
        }
        header nav {
            box-shadow: rgba(0, 0, 0, 0.1) 0px 2px 8px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 8px 0;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .stat-change {
            font-size: 0.875rem;
            font-weight: 600;
            color: #10b981;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            opacity: 0.5;
        }
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 24px;
        }
        .chart-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .chart-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 20px;
        }
        .filter-tabs {
            display: inline-flex;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 3px;
            gap: 4px;
            background-color: #ecedf1;
        }
        .tab-button {
            padding: 0.5rem 1.25rem;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 20px;
            font-weight: 500;
            color: #6b7280;
        }
        .tab-button:hover {
            background-color: #f3f4f6;
        }
        .tab-button.active {
            background-color: white;
            color: black;
        }
        .management-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        .management-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .management-subtitle {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 32px;
        }
        .table-header {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-row {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.2s;
        }
        .table-row:hover {
            background: #f9fafb;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .action-button {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #e5e7eb;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-button:hover {
            background: #f3f4f6;
        }
        .action-approve {
            color: #10b981;
            border-color: #10b981;
        }
        .action-approve:hover {
            background: #d1fae5;
        }
        .action-reject {
            color: #ef4444;
            border-color: #ef4444;
        }
        .action-reject:hover {
            background: #fee2e2;
        }
        .admin-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .admin-nav-link {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .admin-nav-link:hover {
            background-color: #f3f4f6;
        }
        .admin-nav-link.active {
            background-color: black;
            color: white;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        @media (max-width: 768px) {
            .stat-card {
                padding: 16px;
                margin-bottom: 12px;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .stat-icon {
                width: 32px;
                height: 32px;
            }
            .chart-card {
                padding: 16px;
            }
            .management-card {
                padding: 16px;
            }
            .filter-tabs {
                width: 100%;
                justify-content: space-between;
                overflow-x: auto;
            }
            .tab-button {
                padding: 0.4rem 0.8rem;
                font-size: 0.875rem;
                white-space: nowrap;
            }
            .table-row, .table-header {
                font-size: 0.75rem;
                padding: 12px 8px;
            }
            .action-button {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
            .action-button svg {
                width: 12px;
                height: 12px;
            }
            .management-title {
                font-size: 1rem;
            }
            .management-subtitle {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .stat-value {
                font-size: 1.25rem;
            }
            .stat-label {
                font-size: 0.75rem;
            }
            .stat-change {
                font-size: 0.75rem;
            }
            .chart-title {
                font-size: 1rem;
            }
            .chart-subtitle {
                font-size: 0.75rem;
            }
            .filter-tabs {
                flex-wrap: wrap;
            }
            .action-button span {
                display: none;
            }
            .action-button {
                padding: 6px;
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
                            <span class="badge bg-primary d-flex align-items-center px-3 py-2" style="font-size: 0.875rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check me-2" viewBox="0 0 16 16">
                                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                                    <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                                </svg>
                                Admin de l'application
                            </span>
                            <a class="nav-link border rounded-3 d-flex align-items-center" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <div class="container-fluid px-5 py-4">
        <div class="mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#39519d" class="bi bi-person-gear" viewBox="0 0 16 16">
                        <path d="M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m.256 7a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
                        <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-1.993-1.679a.5.5 0 0 0-.686.172l-.5.865-.041-.015a.5.5 0 1 0-.162.819l.064.023a.75.75 0 0 1-.421 1.415l-.064-.023a.5.5 0 1 0-.162.819l.041.015.5.865a.5.5 0 1 0 .858-.514l-.5-.865.041-.015a.5.5 0 1 0 .162-.819L13.072 12l.064-.023a.75.75 0 0 1 .421-1.415l.064.023a.5.5 0 1 0 .162-.819l-.041-.015.5-.865a.5.5 0 1 0-.858-.514z"/>
                    </svg>
                </div>
                <div>
                    <h1 style="color:#39519d; font-size: 2rem; font-weight: 700; margin: 0;">Admin de l'application</h1>
                    <p style="color: #6b7280; margin: 0;">Gérer les utilisateurs, les livres et surveiller les statistiques de la plateforme</p>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-value" id="totalUsers">1,234</div>
                            <div class="stat-change">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                                +12% from last month
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/></svg>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Books</div>
                            <div class="stat-value" id="totalBooks">2,450</div>
                            <div class="stat-change">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                                +18% from last month
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Active Users</div>
                            <div class="stat-value" id="activeUsers">892</div>
                            <div class="stat-change">72.3% of total</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M8.864.046C7.908-.193 7.02.53 6.956 1.466c-.072 1.051-.23 2.016-.428 2.59-.125.36-.479 1.013-1.04 1.639-.557.623-1.282 1.178-2.131 1.41C2.685 7.288 2 7.87 2 8.72v4.001c0 .845.682 1.464 1.448 1.545 1.07.114 1.564.415 2.068.723l.048.03c.272.165.578.348.97.484.397.136.861.217 1.466.217h3.5c.937 0 1.599-.477 1.934-1.064a1.86 1.86 0 0 0 .254-.912c0-.152-.023-.312-.077-.464.201-.263.38-.578.488-.901.11-.33.172-.762.004-1.149.069-.13.12-.269.159-.403.077-.27.113-.568.113-.857 0-.288-.036-.585-.113-.856a2 2 0 0 0-.138-.362 1.9 1.9 0 0 0 .234-1.734c-.206-.592-.682-1.1-1.2-1.272-.847-.282-1.803-.276-2.516-.211a10 10 0 0 0-.443.05 9.4 9.4 0 0 0-.062-4.509A1.38 1.38 0 0 0 9.125.111zM11.5 14.721H8c-.51 0-.863-.069-1.14-.164-.281-.097-.506-.228-.776-.393l-.04-.024c-.555-.339-1.198-.731-2.49-.868-.333-.036-.554-.29-.554-.55V8.72c0-.254.226-.543.62-.65 1.095-.3 1.977-.996 2.614-1.708.635-.71 1.064-1.475 1.238-1.978.243-.7.407-1.768.482-2.85.025-.362.36-.594.667-.518l.262.066c.16.04.258.143.288.255a8.34 8.34 0 0 1-.145 4.725.5.5 0 0 0 .595.644l.003-.001.014-.003.058-.014a9 9 0 0 1 1.036-.157c.663-.06 1.457-.054 2.11.164.175.058.45.3.57.65.107.308.087.67-.266 1.022l-.353.353.353.354c.043.043.105.141.154.315.048.167.075.37.075.581 0 .212-.027.414-.075.582-.05.174-.111.272-.154.315l-.353.353.353.354c.047.047.109.177.005.488a2.2 2.2 0 0 1-.505.805l-.353.353.353.354c.006.005.041.05.041.17a.9.9 0 0 1-.121.416c-.165.288-.503.56-1.066.56z"/></svg>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Transactions</div>
                            <div class="stat-value" id="transactions">3,847</div>
                            <div class="stat-change">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                                +24% from last month
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M11 5.5a.5.5 0 0 1 .5-.5h2.5a.5.5 0 0 1 .5.5v2.5a.5.5 0 0 1-1 0V6.207l-5.146 5.147a.5.5 0 0 1-.708 0L5 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708l5.5-5.5a.5.5 0 0 1 .708 0L8 10.293 12.793 5.5H11.5a.5.5 0 0 1-.5-.5"/></svg>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Pending Moderation</div>
                            <div class="stat-value" id="pendingModeration">23</div>
                            <div class="stat-change" style="color: #f59e0b;">Requires attention</div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value" id="revenue">$12,450</div>
                            <div class="stat-change">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 1 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5"/></svg>
                                +15% from last month
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="stat-icon" fill="#6b7280" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="chart-card">
                    <div class="chart-title">Growth Trends</div>
                    <div class="chart-subtitle">Users and books over the last 6 months</div>
                    <canvas id="growthChart" height="80"></canvas>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="chart-card">
                    <div class="chart-title">Transaction Activity</div>
                    <div class="chart-subtitle">Monthly transactions over the last 6 months</div>
                    <canvas id="transactionChart" height="80"></canvas>
                </div>
            </div>
        </div>
        <div class="filter-tabs mb-4">
            <button class="tab-button active" onclick="switchTab('users')">User Management</button>
            <button class="tab-button" onclick="switchTab('books')">Book Moderation</button>
            <button class="tab-button" onclick="switchTab('allbooks')">All Books</button>
        </div>
        <div id="usersTab" class="management-card">
            <div class="management-title">Recent Users</div>
            <div class="management-subtitle">Manage and monitor user accounts</div>
            
            <div class="table-responsive">
                <div style="display: grid; grid-template-columns: 2fr 2fr 2fr 1.5fr 1fr 1fr; gap: 16px;" class="table-header">
                    <div>Name</div>
                    <div>Email</div>
                    <div>Status</div>
                    <div>Joined</div>
                    <div>Books</div>
                    <div>Actions</div>
                </div>
                <div id="usersTableBody"></div>
            </div>
        </div>
        <div id="booksTab" class="management-card" style="display: none;">
            <div class="management-title">Pending Books</div>
            <div class="management-subtitle">Review and approve book submissions</div>
            
            <div class="table-responsive">
                <div style="display: grid; grid-template-columns: 3fr 2fr 2fr 1.5fr 1.5fr 2fr; gap: 16px;" class="table-header">
                    <div>Title</div>
                    <div>Author</div>
                    <div>Owner</div>
                    <div>Submitted</div>
                    <div>Status</div>
                    <div>Actions</div>
                </div>
                <div id="booksTableBody"></div>
            </div>
        </div>
        <div id="allBooksTab" class="management-card" style="display: none;">
            <div class="management-title">All Books</div>
            <div class="management-subtitle">View and delete all books in the system</div>
            
            <div class="table-responsive">
                <div style="display: grid; grid-template-columns: 3fr 2fr 2fr 1.5fr 1.5fr 1.5fr; gap: 16px;" class="table-header">
                    <div>Title</div>
                    <div>Author</div>
                    <div>Owner</div>
                    <div>Category</div>
                    <div>Status</div>
                    <div>Actions</div>
                </div>
                <div id="allBooksTableBody"></div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            document.getElementById('usersTab').style.display = tab === 'users' ? 'block' : 'none';
            document.getElementById('booksTab').style.display = tab === 'books' ? 'block' : 'none';
            document.getElementById('allBooksTab').style.display = tab === 'allbooks' ? 'block' : 'none';
            
            if (tab === 'allbooks') {
                loadAllBooks();
            }
        }
        async function loadUsers() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_users');
                
                const response = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    const users = data.users;
                    const tbody = document.getElementById('usersTableBody');
                    tbody.innerHTML = users.map(user => {
                        const joinedDate = new Date(user.date_inscription).toLocaleDateString();
                        return `
                            <div style="display: grid; grid-template-columns: 2fr 2fr 2fr 1.5fr 1fr 1fr; gap: 16px; align-items: center;" class="table-row">
                                <div style="font-weight: 500;">${user.nom} ${user.prenom}</div>
                                <div style="color: #6b7280;">${user.email}</div>
                                <div><span class="status-badge status-${user.statut}">${user.statut}</span></div>
                                <div style="color: #6b7280;">${joinedDate}</div>
                                <div style="font-weight: 500;">${user.books_count}</div>
                                <div>
                                    <span class="badge bg-${user.role === 'admin' ? 'danger' : 'secondary'}">${user.role}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }
        let booksData = [];
        async function loadBooks() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_books');
                
                const response = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    booksData = data.books;
                    renderBooks();
                }
            } catch (error) {
                console.error('Error loading books:', error);
            }
        }

        function renderBooks() {
            const tbody = document.getElementById('booksTableBody');
            
            if (booksData.length === 0) {
                tbody.innerHTML = '<div style="text-align: center; padding: 40px; color: #6b7280;">Aucun livre en attente de modération</div>';
                return;
            }
            
            tbody.innerHTML = booksData.map(book => {
                const owner = `${book.nom} ${book.prenom}`;
                const submitted = new Date(book.date_inscription || Date.now()).toLocaleDateString();
                
                return `
                <div style="display: grid; grid-template-columns: 3fr 2fr 2fr 1.5fr 1.5fr 2fr; gap: 16px; align-items: center;" class="table-row" data-book-id="${book.id_livre}">
                    <div style="font-weight: 500;">${book.titre}</div>
                    <div style="color: #6b7280;">${book.auteur}</div>
                    <div style="color: #6b7280;">${owner}</div>
                    <div style="color: #6b7280;">${submitted}</div>
                    <div><span class="status-badge status-pending">En attente</span></div>
                    <div style="display: flex; gap: 8px;">
                        <button class="action-button action-approve" onclick="approveBook(${book.id_livre})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                            Approuver
                        </button>
                        <button class="action-button action-reject" onclick="rejectBook(${book.id_livre})">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                            Rejeter
                        </button>
                    </div>
                </div>
                `;
            }).join('');
        }

        async function approveBook(bookId) {
            const book = booksData.find(b => b.id_livre === bookId);
            if (book) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'approve_book');
                    formData.append('book_id', bookId);
                    
                    const response = await fetch('admin.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        const row = document.querySelector(`[data-book-id="${bookId}"]`);
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        
                        setTimeout(() => {
                            loadBooks();
                            loadStats(); // Refresh stats after approval
                            showNotification('Livre Approuvé', `"${book.titre}" a été approuvé avec succès!`, 'success');
                        }, 300);
                    }
                } catch (error) {
                    console.error('Error approving book:', error);
                }
            }
        }

        async function rejectBook(bookId) {
            const book = booksData.find(b => b.id_livre === bookId);
            if (book) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'reject_book');
                    formData.append('book_id', bookId);
                    
                    const response = await fetch('admin.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        const row = document.querySelector(`[data-book-id="${bookId}"]`);
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        
                        setTimeout(() => {
                            loadBooks();
                            loadStats(); // Refresh stats after rejection
                            showNotification('Livre Rejeté', `"${book.titre}" a été rejeté.`, 'error');
                        }, 300);
                    }
                } catch (error) {
                    console.error('Error rejecting book:', error);
                }
            }
        }

        let allBooksData = [];
        async function loadAllBooks() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_all_books');
                
                const response = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    allBooksData = data.books;
                    const tbody = document.getElementById('allBooksTableBody');
                    tbody.innerHTML = allBooksData.map(book => {
                        const owner = `${book.nom} ${book.prenom}`;
                        
                        let statusBadge = '';
                        if (book.statut === 'en_attente') {
                            statusBadge = '<span class="status-badge status-pending">En attente</span>';
                        } else if (book.statut === 'disponible') {
                            statusBadge = '<span class="status-badge status-active">Disponible</span>';
                        } else {
                            statusBadge = `<span class="status-badge status-inactive">${book.statut}</span>`;
                        }
                        
                        return `
                        <div style="display: grid; grid-template-columns: 3fr 2fr 2fr 1.5fr 1.5fr 1.5fr; gap: 16px; align-items: center;" class="table-row" data-book-id="${book.id_livre}">
                            <div style="font-weight: 500;">${book.titre}</div>
                            <div style="color: #6b7280;">${book.auteur}</div>
                            <div style="color: #6b7280;">${owner}</div>
                            <div style="color: #6b7280;">${book.categorie}</div>
                            <div>${statusBadge}</div>
                            <div style="display: flex; gap: 8px;">
                                <button class="action-button action-reject" onclick="deleteBook(${book.id_livre}, '${book.titre.replace(/'/g, "\\'")}')" title="Supprimer ce livre">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                    Supprimer
                                </button>
                            </div>
                        </div>
                        `;
                    }).join('');
                }
            } catch (error) {
                console.error('Error loading all books:', error);
            }
        }

        async function deleteBook(bookId, bookTitle) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer le livre "${bookTitle}" ? Cette action est irréversible.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_book');
                formData.append('book_id', bookId);
                
                const response = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    const row = document.querySelector(`#allBooksTableBody [data-book-id="${bookId}"]`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                    }
                    
                    setTimeout(() => {
                        loadAllBooks();
                        loadStats(); // Refresh stats after deletion
                        showNotification('Livre Supprimé', `"${bookTitle}" a été supprimé avec succès!`, 'success');
                    }, 300);
                }
            } catch (error) {
                console.error('Error deleting book:', error);
                showNotification('Erreur', 'Impossible de supprimer le livre', 'error');
            }
        }

        function showNotification(title, message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                min-width: 300px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                animation: slideIn 0.3s ease-out;
                z-index: 9999;
                border-left: 4px solid ${type === 'success' ? '#10b981' : '#ef4444'};
            `;
            
            const iconSVG = type === 'success' 
                ? `<svg style="width: 24px; height: 24px; flex-shrink: 0;" fill="#10b981" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`
                : `<svg style="width: 24px; height: 24px; flex-shrink: 0;" fill="#ef4444" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`;
            
            notification.innerHTML = `
                ${iconSVG}
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 2px;">${title}</div>
                    <div style="font-size: 0.875rem; color: #6b7280;">${message}</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        const growthCtx = document.getElementById('growthChart').getContext('2d');
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Books',
                    data: [350, 400, 500, 650, 800, 950],
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    tension: 0.4
                }, {
                    label: 'Users',
                    data: [150, 200, 250, 300, 380, 450],
                    borderColor: '#3b82f6',
                    backgroundColor: 'transparent',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        const transactionCtx = document.getElementById('transactionChart').getContext('2d');
        new Chart(transactionCtx, {
            type: 'bar',
            data: {
                labels: ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Transactions',
                    data: [300, 350, 425, 520, 640, 750],
                    backgroundColor: '#8b5cf6',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadUsers();
            loadBooks();
        });

         async function loadStats() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_stats');
                
                const response = await fetch('admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    const stats = data.stats;
                    animateCounters(stats);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                // Fallback to default values
                animateCounters({
                    totalUsers: 0,
                    totalBooks: 0,
                    activeUsers: 0,
                    totalTransactions: 0,
                    pendingModeration: 0,
                    revenue: 0
                });
            }
        }

         function animateCounters(statsData) {
            const counters = [
                { id: 'totalUsers', target: statsData.totalUsers || 0, duration: 2000, prefix: '', suffix: '', decimals: false },
                { id: 'totalBooks', target: statsData.totalBooks || 0, duration: 2000, prefix: '', suffix: '', decimals: false },
                { id: 'activeUsers', target: statsData.activeUsers || 0, duration: 2000, prefix: '', suffix: '', decimals: false },
                { id: 'transactions', target: statsData.totalTransactions || 0, duration: 2000, prefix: '', suffix: '', decimals: false },
                { id: 'pendingModeration', target: statsData.pendingModeration || 0, duration: 1500, prefix: '', suffix: '', decimals: false },
                { id: 'revenue', target: statsData.revenue || 0, duration: 2000, prefix: '$', suffix: '', decimals: false }
            ];

            counters.forEach(counter => {
                const element = document.getElementById(counter.id);
                if (!element) return;

                const startValue = 0;
                const endValue = counter.target;
                const duration = counter.duration;
                const startTime = performance.now();

                function updateCounter(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                     const easeOut = 1 - Math.pow(1 - progress, 4);
                    const currentValue = Math.floor(startValue + (endValue - startValue) * easeOut);
                    
                     const formattedValue = currentValue.toLocaleString();
                    
                    element.textContent = counter.prefix + formattedValue + counter.suffix;

                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        element.textContent = counter.prefix + endValue.toLocaleString() + counter.suffix;
                    }
                }

                requestAnimationFrame(updateCounter);
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="enhancements.js"></script>
</body>
</html>
