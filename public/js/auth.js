// Manejo de autenticación
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');
            
            try {
                // CORREGIDO: Ruta relativa en lugar de absoluta
                const response = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // CORREGIDO: Ruta relativa
                    window.location.href = 'index.php';
                } else {
                    errorMessage.textContent = data.message || 'Error al iniciar sesión';
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                console.error('Error completo:', error);
                errorMessage.textContent = 'Error de conexión. Intente nuevamente.';
                errorMessage.style.display = 'block';
            }
        });
    }
});

// Función para cerrar sesión
async function logout() {
    try {
        // CORREGIDO: Ruta relativa
        await fetch('api.php?action=logout', {
            method: 'POST'
        });
        window.location.href = 'login.php';
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    }
}

// Verificar autenticación en páginas protegidas
async function checkAuth() {
    try {
        // CORREGIDO: Ruta relativa
        const response = await fetch('api.php?action=check_auth');
        const data = await response.json();
        
        if (!data.authenticated) {
            window.location.href = 'login.php';
        }
        
        return data.user;
    } catch (error) {
        console.error('Error verificando autenticación:', error);
        window.location.href = 'login.php';
    }
}
