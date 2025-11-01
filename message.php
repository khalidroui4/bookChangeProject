<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user'])) {
    header('Location: connexion.php');
    exit;
}

$currentUserId = $_SESSION['user']['id_utilisateur'];

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'send_message') {
        $destinataire_id = intval($_POST['destinataire_id']);
        $contenu = trim($_POST['contenu']);
        
        if (!empty($contenu)) {
            $stmt = $conn->prepare("INSERT INTO message (expediteur_id, destinataire_id, contenu) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $currentUserId, $destinataire_id, $contenu);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Message envoyé']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi']);
            }
            $stmt->close();
        }
        exit;
    }
    
    if ($_POST['action'] === 'get_messages') {
        $other_user_id = intval($_POST['other_user_id']);
        
        $stmt = $conn->prepare("SELECT m.*, u.nom, u.prenom 
                                FROM message m 
                                JOIN utilisateur u ON u.id_utilisateur = m.expediteur_id 
                                WHERE (m.expediteur_id = ? AND m.destinataire_id = ?) 
                                   OR (m.expediteur_id = ? AND m.destinataire_id = ?)
                                ORDER BY m.date_envoi ASC");
        $stmt->bind_param("iiii", $currentUserId, $other_user_id, $other_user_id, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
    
    if ($_POST['action'] === 'get_contacts') {
        // Get all users with whom current user has exchanged messages
        $stmt = $conn->prepare("SELECT DISTINCT u.id_utilisateur, u.nom, u.prenom, u.email,
                                (SELECT contenu FROM message 
                                 WHERE (expediteur_id = u.id_utilisateur AND destinataire_id = ?) 
                                    OR (expediteur_id = ? AND destinataire_id = u.id_utilisateur)
                                 ORDER BY date_envoi DESC LIMIT 1) as last_message,
                                (SELECT date_envoi FROM message 
                                 WHERE (expediteur_id = u.id_utilisateur AND destinataire_id = ?) 
                                    OR (expediteur_id = ? AND destinataire_id = u.id_utilisateur)
                                 ORDER BY date_envoi DESC LIMIT 1) as last_message_time
                                FROM utilisateur u
                                WHERE u.id_utilisateur IN (
                                    SELECT DISTINCT expediteur_id FROM message WHERE destinataire_id = ?
                                    UNION
                                    SELECT DISTINCT destinataire_id FROM message WHERE expediteur_id = ?
                                )
                                AND u.id_utilisateur != ?
                                ORDER BY last_message_time DESC");
        $stmt->bind_param("iiiiiii", $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        $contacts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode(['success' => true, 'contacts' => $contacts]);
        exit;
    }
}

// Handle initial contact from book details page
$initialContact = null;
if (isset($_GET['owner']) && is_numeric($_GET['owner'])) {
    $ownerId = intval($_GET['owner']);
    $stmt = $conn->prepare("SELECT id_utilisateur, nom, prenom, email FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $initialContact = $result->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="enhancements.css">
    <style>
        body{
            background:#f8f9fa;
        }
        .container-chat {
            display: flex;
            height:70vh;
            overflow: hidden;
        }
        .sidebar {
            width: 30%;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .contact-list {
            flex: 1;
            overflow-y: auto;
        }
        .contact {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
        }
        .contact:hover {
            background: #f3f4f6;
        }
        .contact img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .contact .info {
            flex: 1;
        }
        .contact .info h6 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }
        .contact .info p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        .badge {
            font-size: 12px;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }
        .msg {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 18px;
        }
        .msg.received {
            background: #f3f4f6;
            align-self: flex-start;
        }
        .msg.sent {
            background: #2563eb;
            color: white;
            align-self: flex-end;
        }
        .chat-footer {
            border-top: 1px solid #e5e7eb;
            padding: 12px 20px;
            display: flex;
            align-items: center;
        }
        .chat-footer input {
             display: block;
            width: 750px;
            padding: 0.375rem 0.75rem; 
            line-height: 1.5;
            background-clip: padding-box;
            border: 1px solid transparent;
            border-radius: 0.375rem; 
        }
        .chat-footer input:focus{
            border-color: #7d7d7dff;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25); 

        }
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
        .relative input {
            display: block;
            width: 400px;
            padding: 0.375rem 0.75rem; 
            line-height: 1.5;
            background-clip: padding-box;
            border: 1px solid transparent;
            border-radius: 0.375rem; 
        }
        .relative input:focus{
            outline: 0;

        }
        .msg-time {
            font-size: 9px;
            color: #ffffffff;
            margin-top: 7px;
            text-align: right;
            margin-left:10px;
        }
        .chat-container {
           max-width:1200px; 
           margin:auto; 
           padding:2rem 1rem; 
           height:90vh; 
          }
        .contact.active { 
          background-color: #e7f1ff; 
          border-radius: 8px; 
        }
        .message.me .bubble { 
          background:#0d6efd; 
          color:#fff; 
        } 
        .message.other .bubble { 
          background:#f1f3f4; 
        }           
        .bubble { 
          border-radius:1rem; 
          padding:.5rem 1rem; 
          display:inline-block; }
        .scroll-area { 
          overflow-y:auto; 
          max-height:60vh; 
        }

@media (max-width: 992px) {
    section {
        padding: 1.5rem !important;
    }
    .container-chat {
        flex-direction: column !important;
        height: auto !important;
    }
    .sidebar {
        width: 100% !important;
        max-height: 300px;
        border-right: none !important;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 1rem;
    }
    .chat-area {
        margin-left: 0 !important;
        min-height: 500px;
    }
    .contact {
        padding: 10px !important;
    }
    .logo-div {
        width: 40px !important;
        height: 40px !important;
        padding: 8px !important;
    }
    .logo-div svg {
        width: 24px !important;
        height: 24px !important;
    }
}

@media (max-width: 768px) {
    section {
        padding: 1rem !important;
    }
    .container {
        padding: 0 !important;
    }
    .container-chat {
        height: auto !important;
    }
    .sidebar {
        max-height: 250px;
    }
    .chat-area {
        min-height: 450px;
    }
    .chat-header {
        padding: 10px 15px !important;
    }
    .chat-body {
        padding: 15px !important;
    }
    .chat-footer {
        padding: 10px !important;
    }
    .msg {
        max-width: 85% !important;
        font-size: 0.875rem;
        padding: 8px 12px !important;
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
    section {
        padding: 0.75rem !important;
    }
    .container-chat {
        height: auto !important;
    }
    .sidebar {
        max-height: 200px;
    }
    .contact {
        padding: 8px !important;
    }
    .contact img {
        width: 35px !important;
        height: 35px !important;
    }
    .contact .info h6 {
        font-size: 0.875rem;
    }
    .contact .info p {
        font-size: 0.75rem;
    }
    .chat-area {
        min-height: 400px;
    }
    .chat-header strong {
        font-size: 0.95rem;
    }
    .chat-header small {
        font-size: 0.75rem;
    }
    .msg {
        max-width: 90% !important;
        font-size: 0.8rem;
    }
    .chat-footer input {
        font-size: 0.875rem;
    }
    .chat-footer button {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem;
    }
    .logo-div {
        width: 35px !important;
        height: 35px !important;
        padding: 6px !important;
    }
    .logo-div svg {
        width: 20px !important;
        height: 20px !important;
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
                            <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
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
                            <a class="nav-link d-flex align-items-center me-2 active text-light border rounded-3 bg-dark" href="message.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/></svg>
                                <span class="d-none d-lg-inline ms-2">Messages</span>
                            </a>
                            <a class="nav-link d-flex align-items-center me-2" href="historique.php">
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
    <section  style="background-color: #f8f9fa;" class="p-5" >
        <div class="container">
            <p style="color: #3B4FA1;" class="mb-4">Messages</p>
            <div class="container-chat ">
                <div class="sidebar rounded-4 border">
                    <div class="sidebar-header">
                        <div class="relative d-flex align-items-center justify-content-center bg-body-secondary px-2 rounded-3">
                            <svg xmlns="http://www.w3.org/2000/svg" style="color: #a8b0bd;" width="20" height="15" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                            <input type="search" id="contactSearch" placeholder="Search contacts..." class="bg-body-secondary">
                        </div>
                    </div>
                    <div class="contact-list">
                        <?php foreach ($contacts as $c): ?>
                        <div class="contact" onclick="openChat('<?php echo $c['name']; ?>')">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c['name']); ?>&background=E0E7FF&color=1E40AF" alt="">
                            <div class="info">
                                <h6><?php echo $c['name']; ?></h6>
                                <p><?php echo $c['lastMsg']; ?></p>
                            </div>
                            <small><?php echo $c['time']; ?></small>
                            <?php if ($c['unread'] > 0): ?>
                            <span class="badge bg-primary ms-2"><?php echo $c['unread']; ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <div class="chat-area d-flex flex-column border rounded-4 ms-4">
                <div class="chat-header">
                    <div class="d-flex">
                        <div class="logo-div me-3" style="background-color: #dbeafe;color: #1c63fd;padding: 15px;border-radius: 100%; width:fit-content;height:fit-content; ">
                            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" fill="currentColor" class="bi bi-person mx-auto" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                        </div>
                        <div>
                            <strong id="chatUser"><?php echo htmlspecialchars($current); ?></strong><br>
                            <small class="text-muted">Active now</small>
                        </div>  
                    </div>
                </div>
                <div class="chat-body d-flex flex-column" id="chatBody">
                    <div class="msg received">Hi! My name is <span id="chatUser"><?php echo htmlspecialchars($current); ?></span> .</div>
                        <div class="msg received">The book is still available!</div>
                            <div class="msg sent">Yes! Is it still available?</div>
                        </div>
                        <div class="chat-footer">
                            <input type="text" id="msgInput" placeholder="Type a message..." class="bg-body-secondary">
                            <button onclick="sendMsg()" class="btn text-light border rounded-3 ms-2" style="background-color:black;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16"><path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z"/></svg>
                                send
                            </button>
                </div>
            </div>
        </div>
    </section>

<script>
const currentUser = <?= json_encode($_SESSION['user']['email'] ?? 'default') ?>;
const currentUserId = <?= json_encode($_SESSION['user']['id_utilisateur']) ?>;
const initialContact = <?= json_encode($initialContact) ?>;

let selectedName = null;
let selectedUserId = null;
let contactsState = [];
let messagesByUserId = {};

function formatNowTime() {
  const now = new Date();
  const h = now.getHours().toString().padStart(2, '0');
  const m = now.getMinutes().toString().padStart(2, '0');
  return `${h}:${m}`;
}

async function loadContacts() {
  try {
    const formData = new FormData();
    formData.append('action', 'get_contacts');
    
    const response = await fetch('message.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    if (data.success) {
      contactsState = data.contacts.map(c => ({
        id: c.id_utilisateur,
        name: `${c.nom} ${c.prenom}`,
        email: c.email,
        lastMsg: c.last_message || 'Aucun message',
        time: formatDateTime(c.last_message_time || new Date().toISOString()),
        unread: 0
      }));
      
      // Add initial contact if from book details page
      if (initialContact && !contactsState.find(c => c.id == initialContact.id_utilisateur)) {
        contactsState.unshift({
          id: initialContact.id_utilisateur,
          name: `${initialContact.nom} ${initialContact.prenom}`,
          email: initialContact.email,
          lastMsg: 'Nouveau contact',
          time: formatNowTime(),
          unread: 0
        });
      }
      
      rebuildContactListFromState();
      
      // Auto-open first contact or initial contact
      if (initialContact) {
        openChat(initialContact.id_utilisateur, `${initialContact.nom} ${initialContact.prenom}`);
      } else if (contactsState.length > 0) {
        openChat(contactsState[0].id, contactsState[0].name);
      }
    }
  } catch (error) {
    console.error('Error loading contacts:', error);
  }
}

function rebuildContactListFromState() {
  const container = document.querySelector('.contact-list');
  if (!container) return;
  
  if (contactsState.length === 0) {
    container.innerHTML = '<div class="text-center py-3 text-muted">Aucun contact</div>';
    return;
  }
  
  container.innerHTML = '';
  const frag = document.createDocumentFragment();
  
  contactsState.forEach(c => {
    const node = document.createElement('div');
    node.className = 'contact';
    node.onclick = () => openChat(c.id, c.name);
    node.innerHTML = `
      <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(c.name)}&background=E0E7FF&color=1E40AF" alt="">
      <div class="info">
        <h6>${c.name}</h6>
        <p>${c.lastMsg || ''}</p>
      </div>
      <small>${c.time || ''}</small>
      <span class="badge bg-primary ms-2" style="display:${c.unread && Number(c.unread) > 0 ? '' : 'none'}">${c.unread || ''}</span>
    `;
    frag.appendChild(node);
  });
  
  container.appendChild(frag);
}

async function openChat(userId, name) {
  selectedUserId = userId;
  selectedName = name;

  document.querySelectorAll(".contact").forEach(c => {
    const h6 = c.querySelector("h6");
    const isActive = h6 && h6.textContent.trim() === name;
    c.classList.toggle("active", isActive);
  });

  const chatUserElement = document.getElementById("chatUser");
  if (chatUserElement) {
    chatUserElement.textContent = name;
  }

  // Load messages from database
  try {
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('other_user_id', userId);
    
    const response = await fetch('message.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    if (data.success) {
      messagesByUserId[userId] = data.messages;
      renderMessages(userId);
    }
  } catch (error) {
    console.error('Error loading messages:', error);
  }
}

function renderMessages(userId) {
  const chatBody = document.getElementById("chatBody");
  if (!chatBody) return;
  
  chatBody.innerHTML = "";
  const list = messagesByUserId[userId] || [];

  if (list.length === 0) {
    chatBody.innerHTML = "<div class='text-center py-3 text-muted'>Aucun message pour le moment. Commencez la conversation!</div>";
  } else {
    list.forEach(m => {
      const isSent = m.expediteur_id == currentUserId;
      const div = document.createElement("div");
      div.className = "msg " + (isSent ? "sent" : "received");
      div.innerHTML = `
        ${m.contenu}
        <div class="msg-time text-muted small">${formatDateTime(m.date_envoi)}</div>
      `;
      chatBody.appendChild(div);
    });
  }
  chatBody.scrollTop = chatBody.scrollHeight;
}

function formatDateTime(dateStr) {
  const date = new Date(dateStr);
  const h = date.getHours().toString().padStart(2, '0');
  const m = date.getMinutes().toString().padStart(2, '0');
  return `${h}:${m}`;
}

async function sendMsg() {
  const input = document.getElementById("msgInput");
  const text = input.value.trim();
  if (!text || !selectedUserId) return;

  try {
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('destinataire_id', selectedUserId);
    formData.append('contenu', text);
    
    const response = await fetch('message.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    if (data.success) {
      // Add message to local state
      if (!messagesByUserId[selectedUserId]) {
        messagesByUserId[selectedUserId] = [];
      }
      messagesByUserId[selectedUserId].push({
        expediteur_id: currentUserId,
        destinataire_id: selectedUserId,
        contenu: text,
        date_envoi: new Date().toISOString()
      });
      
      // Update contact lastMsg
      const idx = contactsState.findIndex(c => c.id == selectedUserId);
      if (idx !== -1) {
        const updated = { ...contactsState[idx], lastMsg: text, time: formatNowTime(), unread: 0 };
        contactsState.splice(idx, 1);
        contactsState.unshift(updated);
        rebuildContactListFromState();
      }
      
      renderMessages(selectedUserId);
      input.value = "";
    }
  } catch (error) {
    console.error('Error sending message:', error);
  }
}

function filterContacts() {
  const input = document.getElementById("contactSearch");
  const term = (input ? input.value : "").trim().toLowerCase();
  document.querySelectorAll(".contact-list .contact").forEach(c => {
    const name = c.querySelector("h6") ? c.querySelector("h6").textContent.toLowerCase() : "";
    const last = c.querySelector("p") ? c.querySelector("p").textContent.toLowerCase() : "";
    const matches = term === "" || name.includes(term) || last.includes(term);
    c.style.display = matches ? "flex" : "none";
  });
}
document.addEventListener("DOMContentLoaded", () => {
  loadContacts();
  
  const input = document.getElementById("contactSearch");
  if (input) {
    input.addEventListener("input", filterContacts);
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="enhancements.js"></script>
</body>
</html>
