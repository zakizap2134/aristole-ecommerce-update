<?php
// =====================================================
// Login Page - Uses password_verify() for security
// =====================================================

require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Prepared Statement - Prevents SQL Injection
    $sql = "SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    // password_verify() - Requirement
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../public/shop.php');
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .container { max-width: 450px; margin: 80px auto; padding: 20px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card-header { background: #2c3e50; color: white; padding: 15px; border-radius: 10px 10px 0 0; text-align: center; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #27ae60; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #219a52; }
        .alert { padding: 10px; background: #e74c3c; color: white; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .text-center { text-align: center; margin-top: 15px; }
        a { color: #3498db; text-decoration: none; }
        .demo { background: #ecf0f1; padding: 10px; border-radius: 5px; font-size: 12px; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>👟 AristoSole Login</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit">Login</button>
                </form>
                <div class="text-center">
                    <a href="register.php">Don't have an account? Register</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>