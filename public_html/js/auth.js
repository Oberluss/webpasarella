// Authentication functions
function showLoginForm() {
    document.getElementById('loginForm').style.display = 'block';
    document.getElementById('registerForm').style.display = 'none';
    document.getElementById('authModalTitle').textContent = 'Iniciar Sesión';
}

function showRegisterForm() {
    document.getElementById('loginForm').style.display = 'none';
    document.getElementById('registerForm').style.display = 'block';
    document.getElementById('authModalTitle').textContent = 'Registrarse';
}

function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
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
            localStorage.setItem('token', data.token);
            if (data.user.role === 'admin') {
                localStorage.setItem('adminToken', data.token);
            }
            bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
            checkAuth();
            showToast('¡Bienvenido!', 'success');
        } else {
            showToast(data.message || 'Error al iniciar sesión', 'danger');
        }
    })
    .catch(error => {
        showToast('Error al conectar con el servidor', 'danger');
    });
}

function handleRegister(event) {
    event.preventDefault();
    
    const password = document.getElementById('registerPassword').value;
    const passwordConfirm = document.getElementById('registerPasswordConfirm').value;
    
    if (password !== passwordConfirm) {
        showToast('Las contraseñas no coinciden', 'danger');
        return;
    }
    
    const data = {
        firstName: document.getElementById('registerFirstName').value,
        lastName: document.getElementById('registerLastName').value,
        email: document.getElementById('registerEmail').value,
        password: password,
        phone: document.getElementById('registerPhone').value,
        newsletter: document.getElementById('registerNewsletter').checked
    };
    
    fetch('api-proxy.php?path=auth/register', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('¡Registro exitoso! Por favor verifica tu email.', 'success');
            showLoginForm();
            document.getElementById('loginEmail').value = data.email;
        } else {
            showToast(data.message || 'Error al registrar', 'danger');
        }
    })
    .catch(error => {
        showToast('Error al conectar con el servidor', 'danger');
    });
}

function checkAuth() {
    const token = localStorage.getItem('token');
    if (token) {
        fetch('api-proxy.php?path=auth/profile', {
            headers: {
                'Authorization': 'Bearer ' + token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('loginBtn').style.display = 'none';
                document.getElementById('userMenu').style.display = 'block';
                document.getElementById('userName').textContent = data.user.first_name;
                
                if (data.user.role === 'admin') {
                    document.getElementById('adminLink').style.display = 'block';
                }
            } else {
                localStorage.removeItem('token');
                localStorage.removeItem('adminToken');
            }
        })
        .catch(() => {
            localStorage.removeItem('token');
            localStorage.removeItem('adminToken');
        });
    }
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('adminToken');
    window.location.reload();
}

function viewProfile() {
    showToast('Página de perfil en desarrollo', 'info');
}

function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    const toastId = 'toast-' + Date.now();
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}