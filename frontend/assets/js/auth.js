/**
 * auth.js - Validaciones y lógica de autenticación - CORREGIDO
 * 07/11/2025 22:45
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 auth.js cargado - iniciando validaciones');
        const registerForm = document.getElementById('register-form');
        const loginForm = document.getElementById('login-form');
        
        if (registerForm) {
            console.log('📝 Formulario de registro encontrado');
            initializeRegisterValidation(registerForm);
        }
        
        if (loginForm) {
            console.log('🔐 Formulario de login encontrado');
            initializeLoginValidation(loginForm);
        }
        
        initPasswordToggles();
        initUsernameInputs();
    });
    
    // ============================================
    // VALIDACIÓN DE REGISTRO
    // ============================================
    
    function initializeRegisterValidation(form) {
        const usuarioInput = form.querySelector('input[name="usuario"]');
        const nombreInput = form.querySelector('input[name="nombre"]');
        const correoInput = form.querySelector('input[name="correo"]');
        const claveInput = form.querySelector('input[name="clave"]');
        
        console.log('🔄 Inicializando validación de registro');
        
        // Validación en tiempo real
        if (usuarioInput) {
            usuarioInput.addEventListener('blur', function() {
                validateUsername(this);
            });
            usuarioInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        if (nombreInput) {
            nombreInput.addEventListener('blur', function() {
                validateName(this);
            });
            nombreInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        if (correoInput) {
            correoInput.addEventListener('blur', function() {
                validateEmail(this);
            });
            correoInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        if (claveInput) {
            claveInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        // Validar al enviar
        form.addEventListener('submit', function(e) {
            console.log('🔄 Submit del formulario de registro detectado');
            
            const isValid = validateRegistrationForm(form);
            
            if (!isValid) {
                e.preventDefault();
                console.log('❌ Validación falló, formulario NO enviado');
                showGeneralError(form, 'Por favor corrige los errores en el formulario');
            } else {
                console.log('✅ Validación OK, enviando formulario...');
                // Mostrar loading
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
                }
                // NO prevenir el envío - dejar que el formulario se envíe naturalmente
            }
        });
    }
    
    // ============================================
    // VALIDACIÓN DE LOGIN
    // ============================================
    
    function initializeLoginValidation(form) {
        console.log('🔄 Inicializando validación de login');
        const usuarioInput = form.querySelector('input[name="usuario"]');
        const claveInput = form.querySelector('input[name="clave"]');
        
        // Limpiar errores al escribir
        if (usuarioInput) {
            usuarioInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        if (claveInput) {
            claveInput.addEventListener('input', function() {
                if (this.value.length > 0) removeError(this);
            });
        }
        
        // Validar antes de enviar
        form.addEventListener('submit', function(e) {
            console.log('🔄 Validando formulario de login');
            const usuario = usuarioInput ? usuarioInput.value.trim() : '';
            const clave = claveInput ? claveInput.value : '';
            
            let isValid = true;
            
            if (!usuario) {
                showError(usuarioInput, 'El usuario o correo es requerido');
                isValid = false;
            }
            
            if (!clave) {
                showError(claveInput, 'La contraseña es requerida');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                console.log('❌ Validación de login falló');
            } else {
                console.log('✅ Validación de login OK, enviando...');
                // Mostrar loading
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
                }
                // NO prevenir el envío - dejar que el formulario se envíe naturalmente
            }
        });
    }
    
    // ============================================
    // FUNCIONES DE VALIDACIÓN
    // ============================================
    
    function validateUsername(input) {
        const value = input.value.trim();
        
        if (value.length === 0) {
            showError(input, 'El nombre de usuario es requerido');
            return false;
        }
        
        if (value.length < 3) {
            showError(input, 'El usuario debe tener al menos 3 caracteres');
            return false;
        }
        
        if (value.length > 50) {
            showError(input, 'El usuario no puede exceder 50 caracteres');
            return false;
        }
        
        // Solo letras, números y guiones bajos
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        if (!usernameRegex.test(value)) {
            showError(input, 'El usuario solo puede contener letras, números y guiones bajos');
            return false;
        }
        
        showSuccess(input);
        return true;
    }
    
    function validateName(input) {
        const value = input.value.trim();
        
        if (value.length === 0) {
            showError(input, 'El nombre es requerido');
            return false;
        }
        
        if (value.length < 2) {
            showError(input, 'El nombre debe tener al menos 2 caracteres');
            return false;
        }
        
        if (value.length > 100) {
            showError(input, 'El nombre no puede exceder 100 caracteres');
            return false;
        }
        
        // Permite letras, espacios, acentos, puntos, guiones
        const nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.\-']+$/;
        if (!nameRegex.test(value)) {
            showError(input, 'El nombre contiene caracteres no válidos');
            return false;
        }
        
        showSuccess(input);
        return true;
    }
    
    function validateEmail(input) {
        const value = input.value.trim();
        
        if (value.length === 0) {
            showError(input, 'El correo electrónico es requerido');
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showError(input, 'El formato del correo electrónico no es válido');
            return false;
        }
        
        showSuccess(input);
        return true;
    }
    
    function validatePassword(input) {
        const value = input.value;
        
        if (value.length === 0) {
            showError(input, 'La contraseña es requerida');
            return false;
        }
        
        if (value.length < 6) {
            showError(input, 'La contraseña debe tener al menos 6 caracteres');
            return false;
        }
        
        showSuccess(input);
        return true;
    }
    
    function validateRegistrationForm(form) {
        console.log('🔄 Validando formulario completo');
        
        const usuarioInput = form.querySelector('input[name="usuario"]');
        const nombreInput = form.querySelector('input[name="nombre"]');
        const correoInput = form.querySelector('input[name="correo"]');
        const claveInput = form.querySelector('input[name="clave"]');
        
        // Validar cada campo
        const isUsuarioValid = validateUsername(usuarioInput);
        const isNombreValid = validateName(nombreInput);
        const isCorreoValid = validateEmail(correoInput);
        const isClaveValid = validatePassword(claveInput);
        
        console.log('📊 Resultados validación:', {
            usuario: isUsuarioValid,
            nombre: isNombreValid,
            correo: isCorreoValid,
            clave: isClaveValid
        });
        
        const allValid = isUsuarioValid && isNombreValid && isCorreoValid && isClaveValid;
        
        console.log(allValid ? '✅ Formulario válido' : '❌ Formulario inválido');
        return allValid;
    }
    
    // ============================================
    // MOSTRAR ERRORES Y ÉXITOS
    // ============================================
    
    function showError(input, message) {
        if (!input) return;
        
        console.log('❌ Error en campo:', input.name, '-', message);
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;
        
        removeError(input);
        
        inputGroup.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        inputGroup.appendChild(errorDiv);
    }
    
    function showSuccess(input) {
        if (!input) return;
        
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;
        
        inputGroup.classList.remove('error');
        inputGroup.classList.add('success');
        removeError(input);
    }
    
    function removeError(input) {
        if (!input) return;
        
        const inputGroup = input.closest('.input-group');
        if (!inputGroup) return;
        
        inputGroup.classList.remove('error');
        inputGroup.classList.remove('success');
        
        const errorMessage = inputGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }
    
    function showGeneralError(form, message) {
        const existingError = form.querySelector('.general-error');
        if (existingError) {
            existingError.remove();
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'general-error';
        errorDiv.textContent = message;
        
        form.insertBefore(errorDiv, form.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    
    function initUsernameInputs() {
        const usernameInputs = document.querySelectorAll('input[name="usuario"]');
        
        usernameInputs.forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === ' ') {
                    e.preventDefault();
                }
            });
            
            input.addEventListener('input', function() {
                this.value = this.value.replace(/\s/g, '');
            });
        });
    }
    
    function initPasswordToggles() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        
        passwordInputs.forEach(input => {
            const inputGroup = input.closest('.input-group');
            if (!inputGroup) return;
            
            const existingToggle = inputGroup.querySelector('.toggle-password');
            if (existingToggle) return;
            
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'toggle-password';
            toggleBtn.innerHTML = '<i class="far fa-eye"></i>';
            
            inputGroup.style.position = 'relative';
            inputGroup.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }
    
    // ============================================
    // ESTILOS CSS
    // ============================================
    
    const authStyle = document.createElement('style');
    authStyle.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-group {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .input-group.error {
            border-color: #f44336 !important;
        }
        
        .input-group.success {
            border-color: #4CAF50 !important;
        }
        
        .error-message {
            color: #f44336;
            font-size: 0.8rem;
            margin-top: 5px;
            padding-left: 5px;
            animation: fadeIn 0.3s;
        }
        
        .general-error {
            color: #f44336;
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid #f44336;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            animation: fadeIn 0.3s;
            font-size: 0.9rem;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1.2rem;
            z-index: 2;
            transition: color 0.3s;
        }
        
        .toggle-password:hover {
            color: #ff8a00 !important;
        }
        
        .auth-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(authStyle);

    console.log('✅ auth.js completamente inicializado');
})();