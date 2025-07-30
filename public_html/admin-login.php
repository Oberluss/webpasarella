<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - WebPasarella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .login-header {
            background-color: #343a40;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-shield-lock" style="font-size: 3rem;"></i>
            <h3 class="mt-3">Panel de Administración</h3>
        </div>
        <div class="card-body p-4 bg-white" style="border-radius: 0 0 10px 10px;">
            <form onsubmit="handleAdminLogin(event)">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="adminEmail" required value="admin@webprueba.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="adminPassword" required value="admin123">
                </div>
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                </button>
            </form>
            <div class="alert alert-info mt-3">
                <small>
                    <strong>Demo:</strong><br>
                    Email: admin@webprueba.com<br>
                    Pass: admin123
                </small>
            </div>
        </div>
    </div>

    <script>
        function handleAdminLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('adminEmail').value;
            const password = document.getElementById('adminPassword').value;
            
            fetch('api-proxy.php?path=auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Check if user is admin
                    if (data.user.role === 'admin') {
                        localStorage.setItem('adminToken', data.token);
                        window.location.href = 'admin-dashboard.php';
                    } else {
                        alert('Acceso denegado. Se requiere rol de administrador.');
                    }
                } else {
                    alert(data.message || 'Error al iniciar sesión');
                }
            })
            .catch(error => {
                alert('Error al conectar con el servidor');
            });
        }

        // Check if already logged in
        const token = localStorage.getItem('adminToken');
        if (token) {
            // Verify token
            fetch('api-proxy.php?path=auth/profile', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.user.role === 'admin') {
                    window.location.href = 'admin-dashboard.php';
                } else {
                    localStorage.removeItem('adminToken');
                }
            });
        }
    </script>
</body>
</html>