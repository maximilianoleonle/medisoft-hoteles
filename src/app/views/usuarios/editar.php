<?php
/**
 * Vista de edición de usuario - Versión compacta
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
.edit-usuario-view { opacity: 0; transition: opacity 0.3s ease; }
.edit-usuario-view.loaded { opacity: 1; }
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
.info-item {
    background: linear-gradient(135deg, #f9fafb, #f3f4f6);
    border-left: 3px solid var(--hotel-gold);
}
</style>

<div class="edit-usuario-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-user-edit text-lg opacity-80"></i>
                        <?= $esGestionHotel ? 'Editar Trabajador' : 'Editar Usuario' ?>
                        <span class="text-hotel-gold text-sm font-normal ml-2">Modificar información de cuenta</span>
                    </h1>
                </div>
                <a href="<?= url('usuarios') ?>"
                   class="bg-white/10 backdrop-blur text-white px-3 py-1.5 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-1.5 border border-white/20 text-sm">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span>Volver al listado</span>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4 max-w-6xl">
        <!-- Información del usuario actual -->
        <div class="bg-white rounded-lg shadow-sm p-3 mb-4 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 bg-hotel-gold rounded-full flex items-center justify-center">
                        <span class="text-hotel-brown-dark font-bold text-base">
                            <?= strtoupper(substr($usuario['nombre_usuario'], 0, 2)) ?>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($usuario['nombre_completo']) ?>
                        </h2>
                        <p class="text-xs text-gray-500">
                            @<?= htmlspecialchars($usuario['nombre_usuario']) ?> • ID #<?= $usuario['id'] ?>
                        </p>
                    </div>
                </div>
                <div>
                    <?php if ($usuario['activo']): ?>
                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <i class="fas fa-check-circle mr-1"></i>
                            Cuenta Activa
                        </span>
                    <?php else: ?>
                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                            <i class="fas fa-times-circle mr-1"></i>
                            Cuenta Inactiva
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulario principal -->
        <form method="POST" action="<?= url("usuarios/{$usuario['id']}/update") ?>" id="editUserForm">
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
                                       value="<?= old('nombre_usuario', $usuario['nombre_usuario']) ?>"
                                       required
                                       minlength="4"
                                       pattern="[a-zA-Z0-9_]+"
                                       title="Solo letras, números y guiones bajos"
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">
                                Mínimo 4 caracteres
                            </p>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="block text-xs font-semibold text-gray-700 mb-1">
                                Nueva Contraseña
                            </label>
                            <div class="relative">
                                <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       minlength="10"
                                       class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm"
                                       placeholder="••••••••">
                                <button type="button" onclick="togglePassword()" class="absolute right-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 text-sm">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-0.5"></i>
                                Dejar vacío para mantener la contraseña actual
                            </p>
                        </div>

                        <!-- Rol -->
                        <div>
                            <label for="rol" class="block text-xs font-semibold text-gray-700 mb-1">
                                Rol *
                            </label>
                            <?php if ($usuario['id'] == user_id()): ?>
                                <!-- Si es el usuario actual, mostrar rol como texto y enviar como hidden -->
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-user-tag"></i>
                                    </span>
                                    <input type="text"
                                           value="<?= ucfirst($usuario['rol']) ?>"
                                           disabled
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm bg-gray-100 cursor-not-allowed">
                                    <input type="hidden" name="rol" value="<?= $usuario['rol'] ?>">
                                </div>
                                <p class="mt-0.5 text-xs text-amber-600 flex items-center gap-1">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    No puede cambiar su propio rol
                                </p>
                            <?php else: ?>
                                <!-- Si es otro usuario, permitir cambiar el rol -->
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-user-tag"></i>
                                    </span>
                                    <select id="rol" name="rol" required
                                            class="form-input w-full pl-8 pr-8 py-1.5 rounded-md text-sm appearance-none bg-white"
                                            onchange="actualizarPermisos()">
                                        <option value="gerente" <?= $usuario['rol'] == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                        <option value="administrador" <?= $usuario['rol'] == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                        <option value="recepcionista" <?= $usuario['rol'] == 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                    </select>
                                    <span class="absolute right-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none text-xs">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna 2: Información Personal y Sistema -->
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
                                       value="<?= old('nombre_completo', $usuario['nombre_completo']) ?>"
                                       required
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
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
                                       value="<?= old('email', $usuario['email']) ?>"
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
                                       value="<?= old('telefono', $usuario['telefono']) ?>"
                                       class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                       placeholder="(555) 123-4567">
                            </div>
                        </div>
                    </div>

                    <!-- Información del Usuario -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-info-circle text-hotel-brown text-sm"></i>
                            Información del Usuario
                        </h3>

                        <div class="space-y-2">
                            <!-- Estado -->
                            <div class="info-item rounded-md px-3 py-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-700">
                                        <i class="fas fa-toggle-on mr-1.5 text-gray-500"></i>Estado
                                    </span>
                                    <span class="text-xs font-semibold">
                                        <?= $usuario['activo'] ? '<span class="text-emerald-600">Activo</span>' : '<span class="text-red-600">Inactivo</span>' ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Creado -->
                            <div class="info-item rounded-md px-3 py-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-700">
                                        <i class="fas fa-calendar-plus mr-1.5 text-gray-500"></i>Creado
                                    </span>
                                    <span class="text-xs text-gray-900">
                                        <?= format_datetime($usuario['created_at']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Último login -->
                            <div class="info-item rounded-md px-3 py-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-700">
                                        <i class="fas fa-sign-in-alt mr-1.5 text-gray-500"></i>Último login
                                    </span>
                                    <span class="text-xs text-gray-900">
                                        <?= $usuario['ultimo_login'] ? format_datetime($usuario['ultimo_login']) : 'Nunca' ?>
                                    </span>
                                </div>
                            </div>

                            <!-- IP último login -->
                            <?php if ($usuario['ip_ultimo_login']): ?>
                            <div class="info-item rounded-md px-3 py-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-700">
                                        <i class="fas fa-network-wired mr-1.5 text-gray-500"></i>IP último login
                                    </span>
                                    <span class="text-xs text-gray-900 font-mono">
                                        <?= $usuario['ip_ultimo_login'] ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: Permisos y Actividad -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Permisos del Rol -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                            <i class="fas fa-shield-alt text-hotel-brown text-sm"></i>
                            Permisos del Rol
                        </h3>

                        <div id="permisosRol" class="space-y-1.5 text-xs">
                            <!-- Se llenará dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="bg-white rounded-lg shadow-sm p-3 mt-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-asterisk text-xs text-red-500"></i>
                        Los campos marcados con asterisco son obligatorios
                    </div>

                    <div class="flex gap-2">
                        <a href="<?= url('usuarios') ?>"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-all duration-300 flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-times text-xs"></i>
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-hotel-brown text-white rounded-md hover:bg-hotel-brown-dark transition-all duration-300 flex items-center gap-2 text-sm font-medium shadow hover:shadow-md">
                            <i class="fas fa-save text-xs"></i>
                            Actualizar Usuario
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
    const toggleIcon = document.getElementById('toggleIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Permisos por rol (usando los roles ORIGINALES)
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
    const rolActual = rolSelect ? rolSelect.value : '<?= $usuario['rol'] ?>';

    permisosDiv.innerHTML = '';

    if (permisosPorRol[rolActual]) {
        permisosPorRol[rolActual].forEach(item => {
            const permisoHtml = `
                <div class="flex items-center gap-1.5 text-gray-600">
                    <i class="fas ${item.icon} text-hotel-gold w-3"></i>
                    <span>${item.permiso}</span>
                </div>
            `;
            permisosDiv.innerHTML += permisoHtml;
        });
    }
}

// Formateo de teléfono en tiempo real
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
});

// Validación del formulario
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: '¿Guardar cambios?',
        text: 'Se actualizará la información del usuario',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B4423',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Sí, guardar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Actualizando usuario...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar formulario
            this.submit();
        }
    });
});

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    // Marcar como cargado
    const view = document.querySelector('.edit-usuario-view');
    if (view) {
        view.classList.add('loaded');
    }

    // Actualizar permisos iniciales
    actualizarPermisos();

    // Escuchar cambios en el rol si está habilitado
    const rolSelect = document.getElementById('rol');
    if (rolSelect) {
        rolSelect.addEventListener('change', actualizarPermisos);
    }

    // Animar tarjetas
    const cards = document.querySelectorAll('.bg-white');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
        setTimeout(() => {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 50);
    });
});

// Validación visual en tiempo real
const inputs = document.querySelectorAll('.form-input');
inputs.forEach(input => {
    input.addEventListener('blur', function() {
        if (this.checkValidity()) {
            this.classList.add('border-emerald-400');
            this.classList.remove('border-red-400');
        } else if (this.value) {
            this.classList.add('border-red-400');
            this.classList.remove('border-emerald-400');
        }
    });
});
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php clear_old_input(); ?>
