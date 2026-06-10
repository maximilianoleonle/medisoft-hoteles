<?php
/**
 * Vista de creación de usuario - Versión compacta
 * Vista hotelera
 */
$esGestionHotel = $esGestionHotel ?? false;
?>

<!-- Estilos críticos inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.create-usuario-view { opacity: 0; transition: opacity 0.3s ease; }
.create-usuario-view.loaded { opacity: 1; }
.form-input {
    transition: all 0.3s ease;
    border: 1px solid #d1d5db;
}
.form-input:focus {
    border-color: var(--hotel-brown);
    box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
    outline: none;
}
.form-input:valid {
    border-color: #86efac;
}
.form-input:invalid:not(:placeholder-shown) {
    border-color: #fca5a5;
}
.password-strength {
    height: 3px;
    border-radius: 2px;
    transition: all 0.3s ease;
}
</style>

<div class="create-usuario-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-user-plus text-lg opacity-80"></i>
                        <?= $esGestionHotel ? 'Nuevo Trabajador' : 'Nuevo Usuario' ?>
                        <span class="text-hotel-gold text-sm font-normal ml-2">Crear cuenta de acceso</span>
                    </h1>
                </div>
                <a href="<?= url('usuarios') ?>"
                   class="bg-white/10 backdrop-blur text-white px-3 py-1.5 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-1.5 border border-white/20 text-sm">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4 max-w-6xl">
        <!-- Formulario principal -->
        <form method="POST" action="<?= url('usuarios/store') ?>" id="createUserForm">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Columna 1: Datos de Cuenta -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-4 h-full">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-user-circle text-hotel-brown text-sm"></i>
                            Datos de Cuenta
                        </h3>

                        <!-- Nombre de Usuario -->
                        <div class="mb-3">
                            <label for="nombre_usuario" class="block text-xs font-semibold text-gray-700 mb-1">
                                Nombre de Usuario *
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-at"></i>
                                </span>
                                <input type="text"
                                       id="nombre_usuario"
                                       name="nombre_usuario"
                                       value="<?= old('nombre_usuario') ?>"
                                       required
                                       minlength="4"
                                       pattern="[a-zA-Z0-9_]+"
                                       class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm"
                                       placeholder="usuario123"
                                       onkeyup="verificarDisponibilidad()">
                                <span id="availability-icon" class="absolute right-2.5 top-1/2 transform -translate-y-1/2 hidden text-sm">
                                    <i class="fas fa-circle-check"></i>
                                </span>
                            </div>
                            <p id="availability-message" class="mt-0.5 text-xs hidden"></p>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="block text-xs font-semibold text-gray-700 mb-1">
                                Contraseña *
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       required
                                       minlength="10"
                                       class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm"
                                       placeholder="••••••••"
                                       onkeyup="checkPasswordStrength()">
                                <button type="button" onclick="togglePassword()" class="absolute right-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <!-- Indicador de fuerza compacto -->
                            <div class="mt-1">
                                <div class="flex justify-between items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-0.5 mr-2">
                                        <div id="strength-bar" class="password-strength w-0 h-full rounded-full"></div>
                                    </div>
                                    <span id="strength-text" class="text-xs font-medium whitespace-nowrap"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="block text-xs font-semibold text-gray-700 mb-1">
                                Confirmar Contraseña *
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       required
                                       minlength="10"
                                       class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm"
                                       placeholder="••••••••"
                                       onkeyup="checkPasswordMatch()">
                                <span id="match-icon" class="absolute right-2.5 top-1/2 transform -translate-y-1/2 hidden text-sm">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            </div>
                            <p id="match-message" class="mt-0.5 text-xs hidden"></p>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: Información Personal y Rol -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Información Personal -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-id-card text-hotel-brown text-sm"></i>
                            Información Personal
                        </h3>

                        <!-- Nombre Completo -->
                        <div class="mb-3">
                            <label for="nombre_completo" class="block text-xs font-semibold text-gray-700 mb-1">
                                Nombre Completo *
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text"
                                       id="nombre_completo"
                                       name="nombre_completo"
                                       value="<?= old('nombre_completo') ?>"
                                       required
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                       placeholder="Juan Pérez García"
                                       onkeyup="actualizarPreview()">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="block text-xs font-semibold text-gray-700 mb-1">
                                Email <span class="font-normal text-gray-500">(opcional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="<?= old('email') ?>"
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                       placeholder="usuario@hotel.com">
                            </div>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label for="telefono" class="block text-xs font-semibold text-gray-700 mb-1">
                                Teléfono <span class="font-normal text-gray-500">(opcional)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel"
                                       id="telefono"
                                       name="telefono"
                                       value="<?= old('telefono') ?>"
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                       placeholder="(555) 123-4567">
                            </div>
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-shield-alt text-hotel-brown text-sm"></i>
                            Rol y Permisos
                        </h3>

                        <div class="mb-3">
                            <label for="rol" class="block text-xs font-semibold text-gray-700 mb-1">
                                Rol *
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-user-tag"></i>
                                </span>
                                <select id="rol" name="rol" required
                                        class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm appearance-none bg-white"
                                        onchange="actualizarPermisos()">
                                    <option value="">Seleccione un rol</option>
                                    <option value="gerente" <?= old('rol') == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                    <option value="administrador" <?= old('rol') == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                    <option value="recepcionista" <?= old('rol') == 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                </select>
                                <span class="absolute right-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: Vista Previa y Permisos -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Vista Previa -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-eye text-hotel-brown text-sm"></i>
                            Vista Previa
                        </h3>

                        <div class="bg-gray-50 rounded-md p-3">
                            <div class="flex items-center gap-3 mb-3">
                                <div id="preview-avatar" class="h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-gray-500 font-bold text-sm">?</span>
                                </div>
                                <div class="min-w-0">
                                    <p id="preview-nombre" class="font-semibold text-gray-900 text-sm truncate">Nombre del usuario</p>
                                    <p id="preview-usuario" class="text-xs text-gray-500 truncate">@usuario</p>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-xs">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user-tag text-gray-400 w-3"></i>
                                    <span class="text-gray-600">Rol:</span>
                                    <span id="preview-rol" class="font-medium text-gray-900">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-envelope text-gray-400 w-3"></i>
                                    <span class="text-gray-600">Email:</span>
                                    <span id="preview-email" class="font-medium text-gray-900 truncate">-</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-phone text-gray-400 w-3"></i>
                                    <span class="text-gray-600">Tel:</span>
                                    <span id="preview-telefono" class="font-medium text-gray-900">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permisos -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">
                            Permisos del rol:
                        </h4>
                        <div id="permisosRol" class="space-y-1 text-xs">
                            <p class="text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                Selecciona un rol
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="bg-white rounded-lg shadow-sm p-3 mt-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-asterisk text-xs text-red-500"></i>
                        Campos obligatorios
                    </div>

                    <div class="flex gap-2">
                        <a href="<?= url('usuarios') ?>"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-all duration-300 flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-times text-xs"></i>
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-hotel-brown text-white rounded-md hover:bg-hotel-brown-dark transition-all duration-300 flex items-center gap-2 text-sm font-medium shadow hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                                id="submitBtn">
                            <i class="fas fa-user-plus text-xs"></i>
                            Crear Usuario
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Función para mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const toggleIcon = document.getElementById('toggleIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordConfirmInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordConfirmInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Verificar disponibilidad de usuario (simulado)
let checkTimeout;
function verificarDisponibilidad() {
    clearTimeout(checkTimeout);
    const input = document.getElementById('nombre_usuario');
    const icon = document.getElementById('availability-icon');
    const message = document.getElementById('availability-message');

    if (input.value.length < 4) {
        icon.classList.add('hidden');
        message.classList.add('hidden');
        return;
    }

    checkTimeout = setTimeout(() => {
        const disponible = !['admin', 'user', 'test'].includes(input.value);

        icon.classList.remove('hidden');
        message.classList.remove('hidden');

        if (disponible) {
            icon.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i>';
            message.textContent = 'Disponible';
            message.className = 'mt-0.5 text-xs text-emerald-600';
        } else {
            icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
            message.textContent = 'No disponible';
            message.className = 'mt-0.5 text-xs text-red-600';
        }
    }, 500);
}

// Verificar fuerza de contraseña
function checkPasswordStrength() {
    const password = document.getElementById('password').value;
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    const strengthLevels = [
        { width: '25%', color: 'bg-red-500', text: 'Débil' },
        { width: '50%', color: 'bg-orange-500', text: 'Regular' },
        { width: '75%', color: 'bg-yellow-500', text: 'Buena' },
        { width: '100%', color: 'bg-emerald-500', text: 'Excelente' }
    ];

    const level = strengthLevels[strength] || strengthLevels[0];

    strengthBar.className = `password-strength ${level.color}`;
    strengthBar.style.width = password.length > 0 ? level.width : '0';
    strengthText.textContent = password.length > 0 ? level.text : '';
    strengthText.className = `text-xs font-medium ${level.text === 'Débil' ? 'text-red-600' : level.text === 'Regular' ? 'text-orange-600' : level.text === 'Buena' ? 'text-yellow-600' : 'text-emerald-600'}`;
}

// Verificar coincidencia de contraseñas
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;
    const icon = document.getElementById('match-icon');
    const message = document.getElementById('match-message');

    if (confirmation.length === 0) {
        icon.classList.add('hidden');
        message.classList.add('hidden');
        return;
    }

    icon.classList.remove('hidden');
    message.classList.remove('hidden');

    if (password === confirmation) {
        icon.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i>';
        message.textContent = 'Coinciden';
        message.className = 'mt-0.5 text-xs text-emerald-600';
    } else {
        icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
        message.textContent = 'No coinciden';
        message.className = 'mt-0.5 text-xs text-red-600';
    }
}

// Permisos por rol - CORREGIDOS para coincidir con editar.php
const permisosPorRol = {
    gerente: [
        { icon: 'fa-crown', permiso: 'Acceso total al sistema' },
        { icon: 'fa-users', permiso: 'Gestión completa de usuarios' },
        { icon: 'fa-chart-line', permiso: 'Todos los reportes' },
        { icon: 'fa-cog', permiso: 'Configuración del sistema' }
    ],
    administrador: [
        { icon: 'fa-tachometer-alt', permiso: 'Gestión operativa' },
        { icon: 'fa-chart-bar', permiso: 'Reportes' },
        { icon: 'fa-cash-register', permiso: 'Caja' }
    ],
    recepcionista: [
        { icon: 'fa-calendar-check', permiso: 'Check-in/Check-out' },
        { icon: 'fa-bed', permiso: 'Consultas básicas' }
    ]
};

// Actualizar permisos cuando cambie el rol
function actualizarPermisos() {
    const rolSelect = document.getElementById('rol');
    const permisosDiv = document.getElementById('permisosRol');
    const rolActual = rolSelect.value;

    actualizarPreview();

    if (!rolActual) {
        permisosDiv.innerHTML = '<p class="text-gray-500 italic"><i class="fas fa-info-circle mr-1"></i>Selecciona un rol</p>';
        return;
    }

    permisosDiv.innerHTML = '';

    if (permisosPorRol[rolActual]) {
        permisosPorRol[rolActual].forEach(item => {
            permisosDiv.innerHTML += `
                <div class="flex items-center gap-1.5 text-gray-600">
                    <i class="fas ${item.icon} text-hotel-gold w-3"></i>
                    <span>${item.permiso}</span>
                </div>
            `;
        });
    }
}

// Actualizar vista previa
function actualizarPreview() {
    const nombreCompleto = document.getElementById('nombre_completo').value || 'Nombre del usuario';
    const nombreUsuario = document.getElementById('nombre_usuario').value || 'usuario';
    const email = document.getElementById('email').value || '-';
    const telefono = document.getElementById('telefono').value || '-';
    const rol = document.getElementById('rol').value || '-';

    // Avatar
    const avatar = document.getElementById('preview-avatar');
    if (nombreCompleto !== 'Nombre del usuario') {
        const iniciales = nombreCompleto.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        avatar.innerHTML = `<span class="text-hotel-brown-dark font-bold text-sm">${iniciales}</span>`;
        avatar.className = 'h-10 w-10 bg-hotel-gold rounded-full flex items-center justify-center flex-shrink-0';
    }

    document.getElementById('preview-nombre').textContent = nombreCompleto;
    document.getElementById('preview-usuario').textContent = '@' + nombreUsuario;
    document.getElementById('preview-email').textContent = email;
    document.getElementById('preview-telefono').textContent = telefono;
    document.getElementById('preview-rol').textContent = rol ? rol.charAt(0).toUpperCase() + rol.slice(1) : '-';
}

// Formateo de teléfono
document.getElementById('telefono').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formattedValue = '';

    if (value.length > 0) {
        if (value.length <= 3) {
            formattedValue = `(${value}`;
        } else if (value.length <= 6) {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
        } else {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
        }
    }

    e.target.value = formattedValue;
    actualizarPreview();
});

// Validación del formulario
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const password = document.getElementById('password').value;
    const confirmation = document.getElementById('password_confirmation').value;

    if (password !== confirmation) {
        Swal.fire({
            title: 'Error',
            text: 'Las contraseñas no coinciden',
            icon: 'error',
            confirmButtonColor: '#DC2626'
        });
        return;
    }

    const nombreCompleto = document.getElementById('nombre_completo').value;
    const nombreUsuario = document.getElementById('nombre_usuario').value;
    const rol = document.getElementById('rol').value;

    Swal.fire({
        title: '¿Crear usuario?',
        html: `<div class="text-left text-sm">
            <p><strong>Nombre:</strong> ${nombreCompleto}</p>
            <p><strong>Usuario:</strong> @${nombreUsuario}</p>
            <p><strong>Rol:</strong> ${rol.charAt(0).toUpperCase() + rol.slice(1)}</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B4423',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, crear',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.create-usuario-view');
    if (view) view.classList.add('loaded');

    if (document.getElementById('rol').value) actualizarPermisos();

    actualizarPreview();
});

// Listeners
document.getElementById('nombre_completo').addEventListener('input', actualizarPreview);
document.getElementById('nombre_usuario').addEventListener('input', actualizarPreview);
document.getElementById('email').addEventListener('input', actualizarPreview);
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php clear_old_input(); ?>
