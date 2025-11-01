<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';
$emailError = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirm === '') {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif ($password !== $confirm) {
        $passwordError = "Les mots de passe ne correspondent pas.";
    } else {
        // Split full name into prenom and nom (first token = prénom)
        $parts = preg_split('/\s+/', $fullName);
        $prenom = $parts[0] ?? '';
        $nom = trim(substr($fullName, strlen($prenom))) ?: $prenom;

        $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $emailError = "Cet email est déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Check if email ends with @admin.ma to assign admin role
            if (str_ends_with($email, '@admin.ma')) {
                $role = 'admin';
            } else {
                $role = 'user';
            }
            
            $ins = $conn->prepare("INSERT INTO utilisateur(nom, prenom, email, mot_de_passe, role) VALUES(?,?,?,?,?)");
            $ins->bind_param('sssss', $nom, $prenom, $email, $hash, $role);
            if ($ins->execute()) {
                $userId = $ins->insert_id;
                $_SESSION['user'] = [
                    'id_utilisateur' => $userId,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'role' => $role
                ];
                
                // Redirect based on role
                if ($role === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
        $stmt->close();
    }
}
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
    <style>
        header nav{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 5px 15px;
}

.nav-link:hover{
    background-color: rgb(235, 235, 235);
    border-radius: 5px;
}
.relative input {
  display: block;
  width: 100%;
  max-width: 400px;
  padding: 0.375rem 0.75rem; 
  line-height: 1.5;
  background-clip: padding-box;
  border: 1px solid transparent;
  border-radius: 0.375rem; 
}
.relative input:focus{
  outline: 0;
}
.relative2 input {
  display: block;
  width: 100%;
  max-width: 400px;
  padding: 0.375rem 0.75rem; 
  line-height: 1.5;
  background-clip: padding-box;
  border: 1px solid transparent;
  border-radius: 0.375rem; 
}
.relative2 input:focus{
  outline: 0;
}

@media (max-width: 576px) {
  .relative input,
  .relative2 input {
    max-width: 100%;
    font-size: 16px;
  }
  
  .card {
    width: 95% !important;
    padding: 1rem !important;
  }
  
  .relative,
  .relative2 {
    width: 100%;
  }
  
  .alert.alert-info {
    width: 95% !important;
    font-size: 0.875rem;
  }
}
    </style>
</head>
<body>
    <header class="sticky-top bg-light">
        <nav class="navbar navbar-expand-lg" style="background-color:#fefeff;">
            <div class="container-fluid p-2" >
                <img src="images/360_F_1645070415_uc6EMD3WTkVreiUqCr0TczzrZrtU2TLb.jpg" alt="logo" style="width: 50px; margin-left: 10%;">
                <a class="navbar-brand ms-2" href="index.php" >Mon Livre</a>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div class="navbar-nav">
                        <a class="nav-link active ms-5 d-flex" aria-current="page" href="index.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" fill="currentColor" class="bi bi-house-door me-2" viewBox="0 0 16 16"><path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z"/></svg>
                            Accueil
                        </a>
                        <a class="nav-link d-flex ms-2" href="parcourir.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="25" fill="currentColor" class="bi bi-book me-2" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                            Parcourir
                        </a>
                        <div class="container-fluid d-flex" style="margin-left:350px;" >
                            <?php if (!isset($_SESSION['user'])): ?>
                            <a class="nav-link" href="connexion.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-in-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-2a.5.5 0 0 0-1 0v2A1.5 1.5 0 0 0 6.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-8A1.5 1.5 0 0 0 5 3.5v2a.5.5 0 0 0 1 0z"/><path fill-rule="evenodd" d="M11.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H1.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/></svg>
                                Connexion
                            </a>
                            <a class="nav-link text-light border rounded-3 bg-dark ms-2" href="inscrire.php" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16"><path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                                S'inscrire
                            </a>
                            <?php else: ?>
                            <a class="nav-link d-flex me-2" href="dashboard.php">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" fill="currentColor" class="bi bi-layout-wtf mt-1 me-1" viewBox="0 0 16 16"><path d="M5 1v8H1V1zM1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V1a1 1 0 0 0-1-1zm13 2v5H9V2zM9 1a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM5 13v2H3v-2zm-2-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1zm12-1v2H9v-2zm-6-1a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1z"/></svg>                                Tableau de bord
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
    <section class="p-5" style="background-color: #f0f5fa;">
        <div class=" text-center">
             <p style="color: #3B4FA1;" >
                <svg xmlns="http://www.w3.org/2000/svg" style="color: #1c63fd;;" width="40" height="35" fill="currentColor" class="bi bi-book logo   " viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>
                Mon Livre
            </p>
            <p style="color: #3B4FA1;">
               Rejoindre Mon Livre  
            </p>
            <p>
                Créez un compte pour commencer à partager des livres
            </p>
        </div>
        <div class="card card-body mx-auto mt-4 rounded-4" style="width: fit-content;">
            <h6>Inscription</h6>
            <p class="text-secondary">Remplissez vos informations pour créer un compte</p>
            
            
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="alert alert-info" style="max-width: 400px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle me-2" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533z"/>
                    <path d="M9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                </svg>
                <strong>Astuce :</strong> Pour créer un compte administrateur, utilisez un email se terminant par <strong>@admin.ma</strong>
            </div>
            
            <form method="post" class="m-0 p-0">
            <label for="full_name">Nom Complet</label>
            <div class="relative d-flex align-items-center justify-content-center bg-body-secondary px-2 rounded-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" style="color: #a8b0bd;"  width="20" height="20" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/></svg>
                <input type="text" name="full_name" placeholder="Mohamed Khalid" class="bg-body-secondary">
            </div>
            <label for="Email">Email</label>
            <div class="relative d-flex align-items-center justify-content-center bg-body-secondary px-2 rounded-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" style="color: #a8b0bd;" width="15" height="15" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/></svg>
                <input type="email" name="email" placeholder="etudiant@universite.ma" class="bg-body-secondary">
            </div>
            <?php if (!empty($emailError)): ?>
            <small class="text-danger mt-1 d-block"><?php echo htmlspecialchars($emailError); ?></small>
            <?php endif; ?>
            <label for="password">Mots de passe</label>
            <div class="relative2 d-flex align-items-center justify-content-center bg-body-secondary rounded-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" style="color: #a8b0bd;" width="15" height="15" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/></svg>
                <input type="password" name="password" placeholder="********" class="bg-body-secondary">
            </div>
            <label for="password">Confirmer le mot de passe</label>
            <div class="relative2 d-flex align-items-center justify-content-center bg-body-secondary rounded-3">
                <svg xmlns="http://www.w3.org/2000/svg" style="color: #a8b0bd;" width="15" height="15" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 0a4 4 0 0 1 4 4v2.05a2.5 2.5 0 0 1 2 2.45v5a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 13.5v-5a2.5 2.5 0 0 1 2-2.45V4a4 4 0 0 1 4-4M4.5 7A1.5 1.5 0 0 0 3 8.5v5A1.5 1.5 0 0 0 4.5 15h7a1.5 1.5 0 0 0 1.5-1.5v-5A1.5 1.5 0 0 0 11.5 7zM8 1a3 3 0 0 0-3 3v2h6V4a3 3 0 0 0-3-3"/></svg>
                <input type="password" name="confirm_password" placeholder="********" class="bg-body-secondary">
            </div>
            <?php if (!empty($passwordError)): ?>
            <small class="text-danger mt-1 d-block"><?php echo htmlspecialchars($passwordError); ?></small>
            <?php endif; ?>
            <button type="submit" class="btn text-light mt-3 w-100" style="background-color: black;">
                Créer un compte
            </button>
            </form>
            <hr>
            <div class="d-flex mx-auto">
                <p>Vous avez déjà un compte ?</p><a href="connexion.php">Connectez-vous ici</a>
            </div>           
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="enhancements.js"></script>
</body>
</html> 