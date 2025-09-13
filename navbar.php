<?php
// navbar.php
if (!isset($_SESSION)) session_start();

// Verificar si el usuario está autenticado
$isAuthenticated = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Usuario';
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrador';

// Definir los elementos del menú
$menuItems = [
    'dashboard' => [
        'title' => 'Dashboard',
        'icon' => 'speedometer2',
        'url' => 'dashboard.php'
    ],
    'clientes' => [
        'title' => 'Clientes',
        'icon' => 'people',
        'url' => 'clientes.php'
    ],
    'motos' => [
        'title' => 'Motos',
        'icon' => 'bicycle',
        'url' => 'motos.php'
    ],
    'mantenimientos' => [
        'title' => 'Mantenimientos',
        'icon' => 'tools',
        'url' => 'mantenimientos.php',
    ],
    'productos' => [
        'title' => 'Inventario',
        'icon' => 'box-seam',
        'url' => 'productos.php',
    ],
    'ventas' => [
        'title' => 'Ventas',
        'icon' => 'cash-coin',
        'url' => 'ventas.php'
    ],
    'usuarios' => [
        'title' => 'Usuarios',
        'icon' => 'person-gear',
        'url' => 'usuarios.php',
        'admin_only' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --navbar-bg: #2c3e50;
            --navbar-hover: #34495e;
            --primary-accent: #3498db;
            --badge-color: #e74c3c;
        }
        
        .navbar-custom {
            background-color: var(--navbar-bg) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
            color: var(--primary-accent);
        }
        
        .nav-link {
            position: relative;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .nav-link i {
            margin-right: 8px;
            font-size: 1.1rem;
        }
        
        .nav-link:hover, .nav-link:focus {
            background-color: var(--navbar-hover);
            transform: translateY(-2px);
        }
        
        .nav-link.active-nav {
            background-color: var(--primary-accent);
            color: white !important;
        }
        
        /* Estilos para el botón de WhatsApp */
        .whatsapp-btn {
            background-color: #25D366 !important;
            color: white !important;
            border: 1.5px solid #128C7E !important;
            border-radius: 10%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding: 0;
        }
        
        .whatsapp-btn:hover {
            background-color: #128C7E !important;
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .whatsapp-btn i {
            font-size: 1.1rem;
            margin-right: 0 !important;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            padding-left: 1.25rem;
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 1rem 0;
            }
            
            .nav-link {
                margin: 0.25rem 0;
                padding: 0.75rem 1.5rem;
            }
            
            .dropdown-menu {
                margin-left: 1.5rem;
                width: calc(100% - 3rem);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-motorcycle me-2"></i> PatsMotos
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <?php if ($isAuthenticated): ?>
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($menuItems as $key => $item): ?>
                            <?php if (isset($item['admin_only']) && $item['admin_only'] && !$isAdmin) continue; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === $item['url']) ? 'active-nav' : '' ?>" 
                                   href="<?= $item['url'] ?>">
                                    <i class="bi bi-<?= $item['icon'] ?>"></i>
                                    <?= $item['title'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <span class="d-none d-lg-inline"><?= htmlspecialchars($userName) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="perfil.php">
                                        <i class="bi bi-person me-2"></i> Mi Perfil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Resaltar el ítem activo
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active-nav');
                }
            });
            
            // Animación al hacer hover
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.querySelector('.nav-link').style.transform = 'translateY(-2px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.querySelector('.nav-link').style.transform = '';
                });
            });
        });
    </script>
</body>
</html>