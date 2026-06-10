/**
 * Scripts para el sidebar del sistema hotelero
 * Versión: 1.1.0
 * Descripción: Maneja la funcionalidad del sidebar, menú de usuario y respuesta móvil
 * Modificado: Se eliminó la funcionalidad de colapso del sidebar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a los elementos del DOM
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown');
    const searchInput = document.getElementById('sidebar-search-input');
    
    // Asegurar que el sidebar siempre esté expandido
    if (sidebar) {
        // Eliminar cualquier clase que pudiera estar colapsando el sidebar
        sidebar.classList.remove('sidebar-collapsed', 'sidebar-compact', 'collapsed');
    }
    
    // Funcionalidad para el menú de usuario
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            
            // Cerrar menú al hacer clic fuera
            document.addEventListener('click', function closeMenu(e) {
                if (!userDropdown.contains(e.target) && e.target !== userMenuToggle) {
                    userDropdown.classList.remove('show');
                    document.removeEventListener('click', closeMenu);
                }
            });
        });
    }
    
    // Funcionalidad para la barra de búsqueda
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(item => {
                const text = item.querySelector('.nav-text').textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Funcionalidad para móvil
    if (overlay) {
        // Botón de hamburguesa para mostrar sidebar en móvil
        // Asumimos que existe un botón con clase .menu-toggle
        const menuToggle = document.getElementById('mobile-menu-toggle');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevenir scroll
            });
        }
        
        // Ocultar sidebar al hacer clic en overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restaurar scroll
        });
    }
    
    // Cerrar dropdown del usuario al cambiar de página
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (userDropdown) {
                userDropdown.classList.remove('active');
            }
            
            // En móvil, cerrar sidebar al navegar
            if (window.innerWidth <= 768 && sidebar) {
                sidebar.classList.remove('active');
                
                if (overlay) {
                    overlay.classList.remove('active');
                }
                
                document.body.style.overflow = '';
            }
        });
    });
    
    // Prevenir que eventos de clic dentro del sidebar se propaguen al overlay
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Función para ajustar sidebar en cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    });
    
    // Guardar estado activo para persistencia entre páginas
    // Obtener el elemento activo actual
    const activeNavItem = document.querySelector('.nav-item.active');
    
    if (activeNavItem) {
        // Guardar la URL del elemento activo en localStorage
        localStorage.setItem('activeNavItem', activeNavItem.getAttribute('href'));
    }
    
    // Marcar el elemento correcto como activo en futuras cargas
    const savedActiveNavItem = localStorage.getItem('activeNavItem');
    
    if (savedActiveNavItem) {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            if (item.getAttribute('href') === savedActiveNavItem) {
                item.classList.add('active');
            }
        });
    }
});