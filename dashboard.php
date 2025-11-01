<?php 
session_start();
require_once 'config.php';

// Handle book operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id_utilisateur'];

    if (isset($_POST['action']) && $_POST['action'] === 'add_book') {
        $titre = trim($_POST['titre']);
        $auteur = trim($_POST['auteur']);
        $categorie = trim($_POST['categorie']);
        $etat = trim($_POST['etat']);
        $type = trim($_POST['type']);
        $description = trim($_POST['description']);
        
        $image = 'images/png-transparent-computer-icons-book-book-cover-angle-recycling-logo.png'; // Default image
        
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['book_image']['type'];
            $file_size = $_FILES['book_image']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
                $file_extension = pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'book_' . uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = 'images/' . $new_filename;
                
                if (move_uploaded_file($_FILES['book_image']['tmp_name'], $upload_path)) {
                    $image = $upload_path;
                }
            }
        }
        
        $statut = 'en_attente'; 
        $stmt = $conn->prepare("INSERT INTO livre (titre, auteur, categorie, etat, type, description, image, proprietaire_id, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssis", $titre, $auteur, $categorie, $etat, $type, $description, $image, $userId, $statut);
        
        if ($stmt->execute()) {
            $_SESSION['book_success'] = 'Livre ajouté avec succès! En attente d\'approbation par l\'administrateur.';
        }
        $stmt->close();
        header('Location: dashboard.php');
        exit;
    }
    
    // Delete book
    if (isset($_POST['action']) && $_POST['action'] === 'delete_book') {
        $bookId = intval($_POST['book_id']);
        $stmt = $conn->prepare("DELETE FROM livre WHERE id_livre = ? AND proprietaire_id = ?");
        $stmt->bind_param("ii", $bookId, $userId);
        $stmt->execute();
        $stmt->close();
        header('Location: dashboard.php');
        exit;
    }
    
    // Update book
    if (isset($_POST['action']) && $_POST['action'] === 'update_book') {
        $bookId = intval($_POST['book_id']);
        $titre = trim($_POST['titre']);
        $auteur = trim($_POST['auteur']);
        $categorie = trim($_POST['categorie']);
        $etat = trim($_POST['etat']);
        $type = trim($_POST['type']);
        $description = trim($_POST['description']);
        
        // Handle image upload for update
        $updateImage = false;
        $newImage = '';
        
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['book_image']['type'];
            $file_size = $_FILES['book_image']['size'];
            
            // Validate file type and size (max 5MB)
            if (in_array($file_type, $allowed_types) && $file_size <= 5 * 1024 * 1024) {
                $file_extension = pathinfo($_FILES['book_image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'book_' . uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = 'images/' . $new_filename;
                
                if (move_uploaded_file($_FILES['book_image']['tmp_name'], $upload_path)) {
                    $newImage = $upload_path;
                    $updateImage = true;
                }
            }
        }
        
        if ($updateImage) {
            $stmt = $conn->prepare("UPDATE livre SET titre=?, auteur=?, categorie=?, etat=?, type=?, description=?, image=? WHERE id_livre=? AND proprietaire_id=?");
            $stmt->bind_param("sssssssii", $titre, $auteur, $categorie, $etat, $type, $description, $newImage, $bookId, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE livre SET titre=?, auteur=?, categorie=?, etat=?, type=?, description=? WHERE id_livre=? AND proprietaire_id=?");
            $stmt->bind_param("ssssssii", $titre, $auteur, $categorie, $etat, $type, $description, $bookId, $userId);
        }
        
        $stmt->execute();
        $stmt->close();
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch user's books
$userBooks = [];
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id_utilisateur'];
    $stmt = $conn->prepare("SELECT * FROM livre WHERE proprietaire_id = ? ORDER BY id_livre DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userBooks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg">
    <title>Mon Livre - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="enhancements.css">
    <link rel="stylesheet" href="features.css">
    <style>
        .nav-link:hover{
    background-color: rgb(235, 235, 235);
    border-radius: 5px;
}
.navbar-nav a.active{
    background-color: black;
    
    
}
header nav{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
}
.books:hover{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
}
#addBookForm input{
           display: block;
           width:220px;
            padding: 0.375rem 0.75rem; 
            line-height: 1.5;
            background-clip: padding-box;
            border: 1px solid transparent;
            border-radius: 0.375rem; 
}
#addBookForm input:focus{
    border-color: #7d7d7dff;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
}
 #addBookForm select {
  display: block;
  width: 220px;
  padding: 0.375rem 2.25rem 0.375rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  background-image: url("data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' fill='%23343a40' viewBox='0 0 16 16'><path d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z'/></svg>");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 16px 12px;
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
 }
 
 #addBookForm select:focus {
     border-color: #7d7d7dff;
             outline: 0;
             box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
 }

 .custom-dropdown {
     position: relative;
     display: inline-block;
     width: 220px;
 }
 
 .custom-dropdown-toggle {
     display: flex;
     justify-content: space-between;
     align-items: center;
     width: 100%;
     padding: 0.375rem 0.75rem;
     font-size: 1rem;
     line-height: 1.5;
     color: #6c757d;
     background-color: #f8f9fa;
     border: 1px solid transparent;
     border-radius: 0.375rem;
     cursor: pointer;
     user-select: none;
 }
 
 .custom-dropdown-toggle:hover {
     background-color: #e9ecef;
 }
 
 .custom-dropdown-toggle.active {
     border-color: #7d7d7dff;
     box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
 }
 
 .custom-dropdown-arrow {
     transition: transform 0.2s;
 }
 
 .custom-dropdown-toggle.active .custom-dropdown-arrow {
     transform: rotate(180deg);
 }
 
 .custom-dropdown-menu {
     position: absolute;
     top: 100%;
     left: 0;
     right: 0;
     z-index: 1000;
     display: none;
     max-height: 200px;
     overflow-y: auto;
     background-color: #fff;
     border: 1px solid #ced4da;
     border-radius: 0.375rem;
     margin-top: 4px;
     box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
 }
 
 .custom-dropdown-menu.show {
     display: block;
 }
 
 .custom-dropdown-item {
     padding: 0.5rem 0.75rem;
     cursor: pointer;
     transition: background-color 0.15s;
 }
 
 .custom-dropdown-item:hover {
     background-color: #f8f9fa;
 }
 
 .custom-dropdown-item.selected {
     background-color: #e9ecef;
     font-weight: 500;
 }
 
 .toast-container {
     position: fixed;
     top: 20px;
     right: 20px;
     z-index: 9999;
 }
 .custom-toast {
     min-width: 300px;
     background: white;
     border-radius: 8px;
     box-shadow: 0 4px 12px rgba(0,0,0,0.15);
     padding: 16px;
     margin-bottom: 10px;
     display: flex;
     align-items: center;
     gap: 12px;
     animation: slideIn 0.3s ease-out;
 }
 @keyframes slideIn {
     from {
         transform: translateX(400px);
         opacity: 0;
     }
     to {
         transform: translateX(0);
         opacity: 1;
     }
 }
 .custom-toast.success {
     border-left: 4px solid #10b981;
 }
 .custom-toast.error {
     border-left: 4px solid #ef4444;
 }
 .toast-icon {
     width: 24px;
     height: 24px;
     flex-shrink: 0;
 }
 .toast-content {
     flex: 1;
 }
 .toast-title {
     font-weight: 600;
     margin-bottom: 2px;
 }
 .toast-message {
     font-size: 0.875rem;
     color: #6b7280;
 }
 
 #editProfileForm input[type="text"],
 #editProfileForm input[type="email"],
 #editProfileForm input[type="password"] {
     display: block;
     width: 220px;
     padding: 0.375rem 0.75rem; 
     line-height: 1.5;
     background-clip: padding-box;
     border: 1px solid transparent;
     border-radius: 0.375rem; 
 }
 #editProfileForm input[type="text"]:focus,
 #editProfileForm input[type="email"]:focus,
 #editProfileForm input[type="password"]:focus {
     border-color: #7d7d7dff;
     outline: 0;
     box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
 }
 
 #editBookForm input[type="text"],
 #editBookForm input[type="number"] {
     display: block;
     width: 220px;
     padding: 0.375rem 0.75rem; 
     line-height: 1.5;
     background-clip: padding-box;
     border: 1px solid transparent;
     border-radius: 0.375rem; 
 }
 #editBookForm input[type="text"]:focus,
 #editBookForm input[type="number"]:focus {
     border-color: #7d7d7dff;
     outline: 0;
     box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
 }
 #editBookForm textarea {
     display: block;
     width: 450px;
     padding: 0.375rem 0.75rem; 
     line-height: 1.5;
     background-clip: padding-box;
     border: 1px solid transparent;
     border-radius: 0.375rem; 
     resize: none;
 }
 #editBookForm textarea:focus {
     border-color: #7d7d7dff;
     outline: 0;
     box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
 }
 
#addBookForm textarea{
           display: block;
           width:450px;
            padding: 0.375rem 0.75rem; 
            line-height: 1.5;
            background-clip: padding-box;
            border: 1px solid transparent;
            border-radius: 0.375rem; 
            resize:none;
}
#addBookForm textarea:focus{
    border-color: #7d7d7dff;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(69, 69, 69, 0.25);
}

@media (max-width: 992px) {
    section {
        padding: 2rem !important;
    }
    .container {
        max-width: 100% !important;
    }
}

@media (max-width: 768px) {
    section {
        padding: 1.5rem !important;
    }
    .card {
        margin-bottom: 1rem !important;
    }
    .card-body {
        padding: 1rem !important;
    }
    .d-flex.align-items-center.gap-4 {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 1rem !important;
    }
    .p-4 {
        padding: 1rem !important;
    }
    .d-flex.justify-content-between {
        flex-direction: column !important;
        gap: 1rem !important;
        align-items: flex-start !important;
    }
    .modal-content {
        margin: 10px !important;
        max-width: calc(100vw - 20px) !important;
    }
    .modal-body {
        padding: 1rem !important;
    }
    #addBookForm input,
    #addBookForm textarea,
    #editBookForm input,
    #editBookForm textarea,
    #editProfileForm input,
    .custom-dropdown {
        width: 100% !important;
        max-width: 100% !important;
    }
    #addBookForm textarea,
    #editBookForm textarea {
        width: 100% !important;
    }
    .books {
        padding: 1rem !important;
    }
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        width: 100%;
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
        padding: 1rem !important;
    }
    .container {
        padding: 0 !important;
    }
    #profilePhotoDisplay {
        width: 60px !important;
        height: 60px !important;
    }
    #profilePhotoDisplay svg {
        width: 30px !important;
        height: 30px !important;
    }
    h3 {
        font-size: 1.25rem !important;
    }
    h5 {
        font-size: 1rem !important;
    }
    .text-muted {
        font-size: 0.875rem;
    }
    .card {
        border-radius: 0.5rem !important;
    }
    .books {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    .books img {
        width: 100% !important;
        max-width: 150px;
        margin-bottom: 1rem;
    }
    .modal-dialog {
        margin: 0.5rem !important;
    }
    .modal-content {
        max-width: 100vw !important;
    }
    .btn {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.875rem !important;
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
                            <a class="nav-link" href="connexion.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0z"/><path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Connexion
                            </a>
                            <a class="nav-link text-light border rounded-3 bg-dark ms-2" href="inscrire.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                                S'inscrire
                            </a>
                            <?php elseif (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                            <span class="badge me-3 mt-2" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.25rem 0.75rem; border-radius: 20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-shield-check me-1" viewBox="0 0 16 16"><path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/><path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/></svg>
                                Admin
                            </span>
                            <a class="nav-link me-2 active text-light border rounded-3 bg-dark" href="admin.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.39.39 0 0 0-.029-.518z"/><path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/></svg>
                                Admin Dashboard
                            </a>
                            <a class="nav-link border rounded-3 ms-2" href="logout.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/><path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Déconnexion
                            </a>
                            <?php else: ?>
                            <a class="nav-link d-flex align-items-center me-2 active text-light border rounded-3 bg-dark" href="dashboard.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-layout-wtf" viewBox="0 0 16 16"><path d="M5 1v8H1V1zM1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1zm13 2v5H9V2zM9 1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM5 13v2H3v-2zm-2-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-1v2H9v-2zm-6-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/></svg>
                                <span class="d-none d-lg-inline ms-2">Tableau de bord</span>
                            </a>
                            <a class="nav-link d-flex align-items-center me-2" href="message.php">
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
    <section class="p-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="card mb-4">
                <h5 style="color: #3B4FA1;" class="mt-4 ms-4">Mon Profil</h5>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-4">
                        <div id="profilePhotoDisplay" class="rounded-circle d-flex align-items-center justify-content-center" style="background-color:#dbeafe;width: 80px; height: 80px; overflow: hidden;">
                            <svg id="profilePhotoIcon" xmlns="http://www.w3.org/2000/svg" style="color:#155dfc;" width="40" height="40" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                            <img id="profilePhotoImg" src="" alt="Profile" style="display: none; width: 100%; height: 100%; object-fit: cover;" class="rounded-circle">
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-2" style="color: #3B4FA1;"><?php echo htmlspecialchars($_SESSION['user']['prenom'] ?? '') . ' ' . htmlspecialchars($_SESSION['user']['nom'] ?? ''); ?></h3>
                            <div class="d-flex align-items-center gap-2 text-muted mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/>
                                </svg>
                                <span><?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-muted">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                                    <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                                </svg>
                                <span>3 livres listés</span>
                            </div>
                        </div>
                        <button class="btn border" data-bs-toggle="modal" data-bs-target="#editProfileModal">Modifier le profil</button>
                    </div>
                </div>
            </div>

            <div class="card mb-4 recent-views-section">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 style="color: #3B4FA1;">Récemment Consultés</h5>
                            <p class="text-muted mb-0">Livres que vous avez récemment consultés</p>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="if(confirm('Effacer l\'historique?')) {window.historyManager.clearHistory(); loadRecentViews();}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/>
                            </svg>
                            Effacer
                        </button>
                    </div>
                    <div id="recentViewsGrid" class="recent-views-grid">
                        <p class="text-muted text-center py-4">Aucun livre consulté récemment</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 style="color: #3B4FA1;">Mes Livres</h5>
                        <p class="text-muted mb-0">Gérez vos livres listés</p>
                    </div>
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                        </svg>
                        Ajouter un livre
                    </button>
                </div>
                <div class="card-body">
                    <div id="booksContainer" class="d-flex flex-column gap-3">
                        <?php if (empty($userBooks)): ?>
                        <div class="text-center py-5 text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" fill="currentColor" class="bi bi-inbox mb-3 opacity-50" viewBox="0 0 16 16"><path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm-1.17-.437A1.5 1.5 0 0 1 4.98 3h6.04a1.5 1.5 0 0 1 1.17.563l3.7 4.625a.5.5 0 0 1 .106.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374z"/></svg>
                            <p class="mt-2">Aucun livre listé. Ajoutez votre premier livre!</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($userBooks as $book): 
                                $typeBadgeClass = $book['type'] === 'Prêt' ? 'bg-primary' : ($book['type'] === 'Vente' ? 'bg-success' : 'bg-purple');
                                $typeBadgeColor = $book['type'] === 'Échange' ? 'style="background-color: #9333ea; color: white;"' : '';
                                $etatBadgeClass = $book['etat'] === 'Excellent' ? 'bg-success' : ($book['etat'] === 'Bon' ? 'bg-secondary' : 'bg-warning');
                            ?>
                            <div class="books d-flex align-items-center gap-3 p-3 border rounded hover-shadow" data-book-id="<?php echo $book['id_livre']; ?>">
                                <div class="bg-light d-flex align-items-center justify-content-center rounded" style="width: 64px; height: 80px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#999" class="bi bi-book" viewBox="0 0 16 16">
                                        <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                                    </svg>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($book['titre']); ?></h6>
                                    <p class="text-muted mb-2">par <?php echo htmlspecialchars($book['auteur']); ?></p>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($book['categorie']); ?></span>
                                        <span class="badge <?php echo $typeBadgeClass; ?>" <?php echo $typeBadgeColor; ?>><?php echo htmlspecialchars($book['type']); ?></span>
                                        <span class="badge <?php echo $etatBadgeClass; ?>"><?php echo htmlspecialchars($book['etat']); ?></span>
                                        <?php if ($book['statut'] === 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-clock me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                                            </svg>
                                            En attente d'approbation
                                        </span>
                                        <?php elseif ($book['statut'] === 'disponible'): ?>
                                        <span class="badge bg-success">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-check-circle me-1" viewBox="0 0 16 16">
                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                                            </svg>
                                            Approuvé
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-secondary" onclick="editBook(<?php echo $book['id_livre']; ?>, '<?php echo htmlspecialchars($book['titre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['auteur'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['categorie'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['etat'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($book['description'], ENT_QUOTES); ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A .5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                                        </svg>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce livre?');">
                                        <input type="hidden" name="action" value="delete_book">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id_livre']; ?>">
                                        <button type="submit" class="btn btn-outline-danger">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true" >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex px-4 pt-4">
                    <h5 class="" style="margin-right:44%;" id="addBookModalLabel">Ajouter un nouveau livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <p class="text-muted px-4">Remplissez les détails du livre que vous souhaitez lister</p>
                <div class="modal-body px-4">
                    <form id="addBookForm" method="POST" action="dashboard.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_book">
                        <div class="d-flex">
                            <div class="mb-3">
                                <label class="form-label">Titre *</label><br>
                                <input type="text" class="rounded-2 border me-3 bg-body-secondary" name="titre" id="bookTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auteur *</label><br>
                                <input type="text" class="rounded-2 border bg-body-secondary " name="auteur" id="bookAuthor" required>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3 me-3">
                                <label class="form-label">Catégorie *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="bookCategory">
                                        <span class="custom-dropdown-text">Sélectionnez une catégorie</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item" data-value="Informatique">Informatique</div>
                                        <div class="custom-dropdown-item" data-value="Mathématiques">Mathématiques</div>
                                        <div class="custom-dropdown-item" data-value="Physique">Physique</div>
                                        <div class="custom-dropdown-item" data-value="Chimie">Chimie</div>
                                        <div class="custom-dropdown-item" data-value="Commerce">Commerce</div>
                                        <div class="custom-dropdown-item" data-value="Psychologie">Psychologie</div>
                                    </div>
                                    <input type="hidden" name="categorie" id="bookCategory" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">État *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="bookEtat">
                                        <span class="custom-dropdown-text">Sélectionnez un état</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item" data-value="Excellent">Excellent</div>
                                        <div class="custom-dropdown-item" data-value="Bon">Bon</div>
                                        <div class="custom-dropdown-item" data-value="Correct">Correct</div>
                                    </div>
                                    <input type="hidden" name="etat" id="bookEtat" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3 me-3">
                                <label class="form-label">Type *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="bookType">
                                        <span class="custom-dropdown-text">Prêt</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item selected" data-value="Prêt">Prêt</div>
                                        <div class="custom-dropdown-item" data-value="Vente">Vente</div>
                                        <div class="custom-dropdown-item" data-value="Échange">Échange</div>
                                    </div>
                                    <input type="hidden" name="type" id="bookType" value="Prêt" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prix (si vente)</label><br>
                                <input type="number" class="rounded-2 border bg-body-secondary" name="prix" id="bookPrice" placeholder="Optionnel">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label><br>
                            <textarea class="rounded-2 border bg-body-secondary" name="description" id="bookDescription" rows="3" placeholder="Ajoutez des détails supplémentaires sur le livre..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo du livre</label><br>
                            <input type="file" class="form-control bg-body-secondary" name="book_image" id="bookImage" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF, WEBP (Max 5MB)</small>
                        </div>
                        <div class="px-4 pb-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal" onclick="resetAddBookForm()">Annuler</button>
                            <button type="submit" class="btn btn-dark">Ajouter le livre</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex px-4 pt-4">
                    <h5 class="" style="margin-right:60%;" id="editBookModalLabel">Modifier le livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <p class="text-muted px-4">Modifiez les détails du livre</p>
                <div class="modal-body px-4">
                    <form id="editBookForm" method="POST" action="dashboard.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_book">
                        <input type="hidden" name="book_id" id="editBookId">
                        <div class="d-flex">
                            <div class="mb-3">
                                <label class="form-label">Titre *</label><br>
                                <input type="text" class="rounded-2 border me-3 bg-body-secondary" name="titre" id="editBookTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auteur *</label><br>
                                <input type="text" class="rounded-2 border bg-body-secondary" name="auteur" id="editBookAuthor" required>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3 me-3">
                                <label class="form-label">Catégorie *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="editBookCategory">
                                        <span class="custom-dropdown-text">Sélectionnez une catégorie</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item" data-value="Informatique">Informatique</div>
                                        <div class="custom-dropdown-item" data-value="Mathématiques">Mathématiques</div>
                                        <div class="custom-dropdown-item" data-value="Physique">Physique</div>
                                        <div class="custom-dropdown-item" data-value="Chimie">Chimie</div>
                                        <div class="custom-dropdown-item" data-value="Commerce">Commerce</div>
                                        <div class="custom-dropdown-item" data-value="Psychologie">Psychologie</div>
                                    </div>
                                    <input type="hidden" name="categorie" id="editBookCategory" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">État *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="editBookEtat">
                                        <span class="custom-dropdown-text">Sélectionnez un état</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item" data-value="Excellent">Excellent</div>
                                        <div class="custom-dropdown-item" data-value="Bon">Bon</div>
                                        <div class="custom-dropdown-item" data-value="Correct">Correct</div>
                                    </div>
                                    <input type="hidden" name="etat" id="editBookEtat" required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3 me-3">
                                <label class="form-label">Type *</label><br>
                                <div class="custom-dropdown">
                                    <div class="custom-dropdown-toggle" data-dropdown="editBookType">
                                        <span class="custom-dropdown-text">Prêt</span>
                                        <svg class="custom-dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592c.86 0 1.32 1.013.753 1.658l-4.796 5.482a1 1 0 0 1-1.506 0z"/>
                                        </svg>
                                    </div>
                                    <div class="custom-dropdown-menu">
                                        <div class="custom-dropdown-item" data-value="Prêt">Prêt</div>
                                        <div class="custom-dropdown-item" data-value="Vente">Vente</div>
                                        <div class="custom-dropdown-item" data-value="Échange">Échange</div>
                                    </div>
                                    <input type="hidden" name="type" id="editBookType" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prix (si vente)</label><br>
                                <input type="number" class="rounded-2 border bg-body-secondary" id="editBookPrice" placeholder="Optionnel">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label><br>
                            <textarea class="rounded-2 border bg-body-secondary" name="description" id="editBookDescription" rows="3" placeholder="Ajoutez des détails supplémentaires sur le livre..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Changer la photo du livre</label><br>
                            <input type="file" class="form-control bg-body-secondary" name="book_image" id="editBookImage" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF, WEBP (Max 5MB). Laissez vide pour garder l'image actuelle.</small>
                        </div>
                        <div class="px-4 pb-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-dark">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="d-flex px-4 pt-4">
                    <h5 class="" style="margin-right:60%;" id="editProfileModalLabel">Modifier le profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <p class="text-muted px-4">Mettez à jour vos informations personnelles</p>
                <div class="modal-body px-4">
                    <form id="editProfileForm">
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" style="background-color:#dbeafe;width: 100px; height: 100px;" id="profilePreview">
                                    <svg xmlns="http://www.w3.org/2000/svg" style="color:#155dfc;" width="50" height="50" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                                </div>
                                <label for="profilePicture" class="btn btn-sm btn-dark position-absolute bottom-0 end-0 rounded-circle" style="cursor: pointer; padding: 0.25rem 0.5rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-camera" viewBox="0 0 16 16">
                                        <path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h2.172a2 2 0 0 0 1.414-.586l.828-.828A2 2 0 0 1 6.828 4h2.344a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 13.828 5H16a1 1 0 0 1 1 1zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 4.172 3H2z"/>
                                        <path d="M8 11a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5m0 1a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7m-3.5-3.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                    </svg>
                                </label>
                                <input type="file" id="profilePicture" accept="image/*" style="display: none;" onchange="previewProfilePicture(this)">
                            </div>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3">
                                <label class="form-label">Prénom *</label><br>
                                <input type="text" class="rounded-2 border me-3 bg-body-secondary" id="editFirstName" value="<?php echo htmlspecialchars($_SESSION['user']['prenom'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom *</label><br>
                                <input type="text" class="rounded-2 border bg-body-secondary" id="editLastName" value="<?php echo htmlspecialchars($_SESSION['user']['nom'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label><br>
                            <input type="email" class="rounded-2 border bg-body-secondary" style="width: 450px;" id="editEmail" value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" required>
                        </div>
                        <div class="d-flex">
                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label><br>
                                <input type="password" class="rounded-2 border me-3 bg-body-secondary" id="editPassword" placeholder="Laisser vide si inchangé">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmer le mot de passe</label><br>
                                <input type="password" class="rounded-2 border bg-body-secondary" id="editPasswordConfirm" placeholder="Laisser vide si inchangé">
                            </div>
                        </div>
                        <small id="passwordMismatchError" class="text-danger d-none">Les mots de passe ne correspondent pas</small>
                    </form>
                </div>
                <div class="px-4 pb-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-dark" onclick="saveProfile()">Enregistrer les modifications</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script>
    function showToast(title, message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;
        
        const iconSVG = type === 'success' 
            ? `<svg class="toast-icon" fill="#10b981" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`
            : `<svg class="toast-icon" fill="#ef4444" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>`;
        
        toast.innerHTML = `
            ${iconSVG}
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function resetAddBookForm() {
        document.getElementById('bookTitle').value = '';
        document.getElementById('bookAuthor').value = '';
        document.getElementById('bookPrice').value = '';
        document.getElementById('bookDescription').value = '';
        document.getElementById('bookImage').value = '';
        const categoryDropdown = document.querySelector('[data-dropdown="bookCategory"]');
        const categoryText = categoryDropdown.querySelector('.custom-dropdown-text');
        const categoryInput = document.getElementById('bookCategory');
        categoryText.textContent = 'Sélectionnez une catégorie';
        categoryInput.value = '';
        categoryDropdown.parentElement.querySelectorAll('.custom-dropdown-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        const etatDropdown = document.querySelector('[data-dropdown="bookEtat"]');
        const etatText = etatDropdown.querySelector('.custom-dropdown-text');
        const etatInput = document.getElementById('bookEtat');
        etatText.textContent = 'Sélectionnez un état';
        etatInput.value = '';
        etatDropdown.parentElement.querySelectorAll('.custom-dropdown-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        const typeDropdown = document.querySelector('[data-dropdown="bookType"]');
        const typeText = typeDropdown.querySelector('.custom-dropdown-text');
        const typeInput = document.getElementById('bookType');
        typeText.textContent = 'Prêt';
        typeInput.value = 'Prêt';
        typeDropdown.parentElement.querySelectorAll('.custom-dropdown-item').forEach(item => {
            item.classList.remove('selected');
            if (item.getAttribute('data-value') === 'Prêt') {
                item.classList.add('selected');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initCustomDropdowns();
        
        const addBookModal = document.getElementById('addBookModal');
        addBookModal.addEventListener('hidden.bs.modal', function () {
            resetAddBookForm();
        });
    });

    function initCustomDropdowns() {
        const dropdowns = document.querySelectorAll('.custom-dropdown');
        
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.custom-dropdown-toggle');
            const menu = dropdown.querySelector('.custom-dropdown-menu');
            const items = dropdown.querySelectorAll('.custom-dropdown-item');
            const text = dropdown.querySelector('.custom-dropdown-text');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const dropdownId = toggle.getAttribute('data-dropdown');
            
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                document.querySelectorAll('.custom-dropdown-menu.show').forEach(m => {
                    if (m !== menu) {
                        m.classList.remove('show');
                        m.previousElementSibling.classList.remove('active');
                    }
                });
                menu.classList.toggle('show');
                toggle.classList.toggle('active');
            });
            
            items.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const value = this.getAttribute('data-value');
                    text.textContent = value;
                    hiddenInput.value = value;
                    
                    items.forEach(i => i.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    menu.classList.remove('show');
                    toggle.classList.remove('active');
                });
            });
        });
        
        document.addEventListener('click', function() {
            document.querySelectorAll('.custom-dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
                menu.previousElementSibling.classList.remove('active');
            });
        });
    }

    // Form handling is now done via PHP POST requests

    function editBook(id, title, author, category, type, etat, description) {
        document.getElementById('editBookId').value = id;
        document.getElementById('editBookTitle').value = title;
        document.getElementById('editBookAuthor').value = author;
        document.getElementById('editBookCategory').value = category;
        document.getElementById('editBookType').value = type;
        document.getElementById('editBookEtat').value = etat;
        document.getElementById('editBookDescription').value = description;
        
        // Update dropdown displays
        const categoryToggle = document.querySelector('[data-dropdown="editBookCategory"]');
        if (categoryToggle) categoryToggle.querySelector('.custom-dropdown-text').textContent = category;
        
        const typeToggle = document.querySelector('[data-dropdown="editBookType"]');
        if (typeToggle) typeToggle.querySelector('.custom-dropdown-text').textContent = type;
        
        const etatToggle = document.querySelector('[data-dropdown="editBookEtat"]');
        if (etatToggle) etatToggle.querySelector('.custom-dropdown-text').textContent = etat;

        const editModal = new bootstrap.Modal(document.getElementById('editBookModal'));
        editModal.show();
    }

    // Edit and delete are now handled via PHP POST requests

    function loadProfilePhoto() {
        const savedPhoto = localStorage.getItem(`profilePhoto_${<?php echo json_encode($_SESSION['user']['email'] ?? 'default'); ?>}`);
        if (savedPhoto) {
            const img = document.getElementById('profilePhotoImg');
            const icon = document.getElementById('profilePhotoIcon');
            img.src = savedPhoto;
            img.style.display = 'block';
            icon.style.display = 'none';
        }
    }

    function previewProfilePicture(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profilePreview');
                const bgDiv = preview;
                bgDiv.innerHTML = `<img src="${e.target.result}" class="w-100 h-100" style="object-fit: cover;">`;
                
                localStorage.setItem(`profilePhoto_${<?php echo json_encode($_SESSION['user']['email'] ?? 'default'); ?>}`, e.target.result);
                
                const img = document.getElementById('profilePhotoImg');
                const icon = document.getElementById('profilePhotoIcon');
                img.src = e.target.result;
                img.style.display = 'block';
                icon.style.display = 'none';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function saveProfile() {
        const firstName = document.getElementById('editFirstName').value;
        const lastName = document.getElementById('editLastName').value;
        const email = document.getElementById('editEmail').value;
        const password = document.getElementById('editPassword').value;
        const passwordConfirm = document.getElementById('editPasswordConfirm').value;

        if (!firstName || !lastName || !email) {
            showToast('Champs requis', 'Veuillez remplir tous les champs requis', 'error');
            return;
        }

        if (password || passwordConfirm) {
            if (password !== passwordConfirm) {
                document.getElementById('passwordMismatchError').classList.remove('d-none');
                showToast('Erreur', 'Les mots de passe ne correspondent pas', 'error');
                return;
            }
        }

        const userData = {
            firstName: firstName,
            lastName: lastName,
            email: email
        };
        if (password) {
            userData.password = password;
        }
        
        const userKey = `userProfile_${<?php echo json_encode($_SESSION['user']['email'] ?? 'default'); ?>}`;
        localStorage.setItem(userKey, JSON.stringify(userData));
        
        showToast('Profil mis à jour', 'Votre profil a été mis à jour avec succès!', 'success');
        
        document.querySelector('.card-body h3').textContent = `${firstName} ${lastName}`;
        const emailSpans = document.querySelectorAll('.card-body .text-muted span');
        if (emailSpans.length > 0) {
            emailSpans[0].textContent = email;
        }

        document.getElementById('passwordMismatchError').classList.add('d-none');
        document.getElementById('editPassword').value = '';
        document.getElementById('editPasswordConfirm').value = '';
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
        modal.hide();
    }

    function loadProfileData() {
        const userKey = `userProfile_${<?php echo json_encode($_SESSION['user']['email'] ?? 'default'); ?>}`;
        const savedData = localStorage.getItem(userKey);
        
        if (savedData) {
            const userData = JSON.parse(savedData);
            
            document.querySelector('.card-body h3').textContent = `${userData.firstName} ${userData.lastName}`;
            const emailSpans = document.querySelectorAll('.card-body .text-muted span');
            if (emailSpans.length > 0) {
                emailSpans[0].textContent = userData.email;
            }
            
            document.getElementById('editFirstName').value = userData.firstName;
            document.getElementById('editLastName').value = userData.lastName;
            document.getElementById('editEmail').value = userData.email;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadProfilePhoto();
        loadProfileData();
        
        // Show success message if book was added
        <?php if (isset($_SESSION['book_success'])): ?>
        showToast('Succès', '<?php echo $_SESSION['book_success']; ?>', 'success');
        <?php unset($_SESSION['book_success']); ?>
        <?php endif; ?>
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="enhancements.js"></script>
    <script src="features.js"></script>
    <script>
        function loadRecentViews() {
            if (!window.historyManager) return;
            
            const history = window.historyManager.getHistory();
            const container = document.getElementById('recentViewsGrid');
            
            if (!container) return;
            
            if (history.length === 0) {
                container.innerHTML = '<p class="text-muted text-center py-4">Aucun livre consulté récemment</p>';
                return;
            }
            
            container.innerHTML = history.map(book => `
                <a href="details.php?id=${book.id}" class="recent-view-item text-decoration-none">
                    <img src="${book.image}" alt="${book.title}">
                    <div class="recent-view-overlay">
                        <div class="fw-semibold">${book.title}</div>
                        <small>${book.category}</small>
                    </div>
                </a>
            `).join('');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentViews();
        });
    </script>
</body>
</html>

