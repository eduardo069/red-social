/**
 * avatar-selector.js - Sistema de selección de avatares
 * Incluye modal, preview y upload
 * frontend/assets/js/avatar-selector.js
 */

(function() {
    'use strict';
    
    const API_BASE = '/red-social/backend/api/users.php';
    let availableAvatars = [];
    let selectedAvatar = null;
    let selectedFile = null;
    
    // ============================================
    // INICIALIZACIÓN
    // ============================================
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎭 Sistema de avatares inicializado');
        initAvatarSystem();
    });
    
    /**
     * Inicializar sistema de avatares
     */
    function initAvatarSystem() {
        // Crear modal en el DOM
        createAvatarModal();
        
        // Cargar avatares disponibles
        loadAvailableAvatars();
        
        // Event listeners
        setupEventListeners();
    }
    
    /**
     * Crear estructura HTML del modal
     */
    function createAvatarModal() {
        const modalHTML = `
            <div id="avatar-modal" class="avatar-modal">
                <div class="avatar-modal-content">
                    <div class="avatar-modal-header">
                        <h2><i class="fas fa-user-circle"></i> Cambiar foto de perfil</h2>
                        <button class="avatar-modal-close" id="close-avatar-modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="avatar-modal-body">
                        <!-- Preview actual -->
                        <div class="avatar-preview-section">
                            <div class="avatar-preview-current">
                                <img id="avatar-preview-img" src="" alt="Preview">
                            </div>
                            <p class="avatar-preview-label">Vista previa</p>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="avatar-tabs">
                            <button class="avatar-tab active" data-tab="predefined">
                                <i class="fas fa-images"></i> Avatares
                            </button>
                            <button class="avatar-tab" data-tab="upload">
                                <i class="fas fa-upload"></i> Subir foto
                            </button>
                        </div>
                        
                        <!-- Tab: Avatares predeterminados -->
                        <div class="avatar-tab-content active" id="tab-predefined">
                            <div class="avatar-grid" id="avatar-grid">
                                <div class="avatar-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Cargando avatares...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab: Subir foto -->
                        <div class="avatar-tab-content" id="tab-upload">
                            <div class="avatar-upload-zone" id="avatar-upload-zone">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Arrastra una imagen aquí o haz click para seleccionar</p>
                                <p class="avatar-upload-hint">JPG o PNG · Máximo 2MB</p>
                                <input type="file" id="avatar-file-input" accept="image/jpeg,image/jpg,image/png" hidden>
                            </div>
                            <div class="avatar-upload-preview" id="avatar-upload-preview" style="display: none;">
                                <img id="avatar-upload-preview-img" src="" alt="Preview">
                                <button class="avatar-remove-upload" id="remove-upload">
                                    <i class="fas fa-times"></i> Quitar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="avatar-modal-footer">
                        <button class="btn-cancel" id="cancel-avatar-btn">Cancelar</button>
                        <button class="btn-save" id="save-avatar-btn" disabled>
                            <i class="fas fa-check"></i> Guardar cambios
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        addAvatarStyles();
    }
    
    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Abrir modal (desde cualquier foto de perfil con clase .change-avatar)
        document.addEventListener('click', function(e) {
            if (e.target.closest('.change-avatar') || e.target.closest('[data-open-avatar-modal]')) {
                e.preventDefault();
                openAvatarModal();
            }
        });
        
        // Cerrar modal
        const modal = document.getElementById('avatar-modal');
        const closeBtn = document.getElementById('close-avatar-modal');
        const cancelBtn = document.getElementById('cancel-avatar-btn');
        
        closeBtn?.addEventListener('click', closeAvatarModal);
        cancelBtn?.addEventListener('click', closeAvatarModal);
        
        modal?.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAvatarModal();
            }
        });
        
        // Tabs
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                switchTab(this.dataset.tab);
            });
        });
        
        // Upload zone
        const uploadZone = document.getElementById('avatar-upload-zone');
        const fileInput = document.getElementById('avatar-file-input');
        
        uploadZone?.addEventListener('click', () => fileInput?.click());
        
        fileInput?.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
        
        // Drag & drop
        uploadZone?.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone?.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone?.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        // Remove upload
        document.getElementById('remove-upload')?.addEventListener('click', removeUpload);
        
        // Save button
        document.getElementById('save-avatar-btn')?.addEventListener('click', saveAvatar);
    }
    
    /**
     * Cargar avatares disponibles desde API
     */
    async function loadAvailableAvatars() {
        try {
            const response = await fetch(`${API_BASE}?action=get-avatars`);
            const result = await response.json();
            
            if (result.success) {
                availableAvatars = result.data;
                renderAvatarGrid();
            } else {
                showError('Error al cargar avatares');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
        }
    }
    
    /**
     * Renderizar grid de avatares
     */
    function renderAvatarGrid() {
        const grid = document.getElementById('avatar-grid');
        
        if (availableAvatars.length === 0) {
            grid.innerHTML = '<p class="no-avatars">No hay avatares disponibles</p>';
            return;
        }
        
        grid.innerHTML = availableAvatars.map(avatar => `
            <div class="avatar-option" data-avatar="${avatar.name}">
                <img src="${avatar.url}" alt="${avatar.name}">
                <div class="avatar-option-check">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        `).join('');
        
        // Event listeners para selección
        grid.querySelectorAll('.avatar-option').forEach(option => {
            option.addEventListener('click', function() {
                selectAvatar(this.dataset.avatar);
            });
        });
    }
    
    /**
     * Seleccionar avatar predeterminado
     */
    function selectAvatar(avatarName) {
        selectedAvatar = avatarName;
        selectedFile = null;
        
        // Marcar como seleccionado
        document.querySelectorAll('.avatar-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        document.querySelector(`[data-avatar="${avatarName}"]`)?.classList.add('selected');
        
        // Actualizar preview
        const avatar = availableAvatars.find(a => a.name === avatarName);
        if (avatar) {
            document.getElementById('avatar-preview-img').src = avatar.url;
        }
        
        // Habilitar botón guardar
        document.getElementById('save-avatar-btn').disabled = false;
    }
    
    /**
     * Manejar selección de archivo
     */
    function handleFileSelect(file) {
        // Validar tipo
        if (!file.type.match('image/(jpeg|jpg|png)')) {
            showError('Solo se permiten imágenes JPG o PNG');
            return;
        }
        
        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showError('La imagen no puede superar 2MB');
            return;
        }
        
        selectedFile = file;
        selectedAvatar = null;
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('avatar-upload-preview-img');
            const preview = document.getElementById('avatar-preview-img');
            
            previewImg.src = e.target.result;
            preview.src = e.target.result;
            
            document.getElementById('avatar-upload-zone').style.display = 'none';
            document.getElementById('avatar-upload-preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        // Habilitar botón guardar
        document.getElementById('save-avatar-btn').disabled = false;
    }
    
    /**
     * Quitar archivo seleccionado
     */
    function removeUpload() {
        selectedFile = null;
        document.getElementById('avatar-file-input').value = '';
        document.getElementById('avatar-upload-zone').style.display = 'flex';
        document.getElementById('avatar-upload-preview').style.display = 'none';
        document.getElementById('save-avatar-btn').disabled = true;
    }
    
    /**
     * Guardar avatar
     */
    async function saveAvatar() {
        const saveBtn = document.getElementById('save-avatar-btn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        
        try {
            const formData = new FormData();
            
            if (selectedAvatar) {
                // Avatar predeterminado
                formData.append('avatar_name', selectedAvatar);
            } else if (selectedFile) {
                // Foto personalizada
                formData.append('photo', selectedFile);
            }
            
            const response = await fetch(`${API_BASE}?action=update-avatar`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('Foto de perfil actualizada');
                
                // Actualizar foto en la página
                if (result.data && result.data.foto_perfil) {
                    updateProfileImages(result.data.foto_perfil);
                }
                
                setTimeout(() => {
                    closeAvatarModal();
                    location.reload(); // Recargar para ver cambios
                }, 1000);
            } else {
                showError(result.message || 'Error al guardar');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-check"></i> Guardar cambios';
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error de conexión');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-check"></i> Guardar cambios';
        }
    }
    
    /**
     * Actualizar imágenes de perfil en la página
     */
    function updateProfileImages(newPhotoUrl) {
        const fullUrl = `/red-social/backend/${newPhotoUrl}`;
        document.querySelectorAll('.profile-photo, .user-avatar, .post-avatar img').forEach(img => {
            img.src = fullUrl;
        });
    }
    
    /**
     * Cambiar tab
     */
    function switchTab(tabName) {
        // Tabs
        document.querySelectorAll('.avatar-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
        
        // Content
        document.querySelectorAll('.avatar-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(`tab-${tabName}`)?.classList.add('active');
        
        // Reset selection
        selectedAvatar = null;
        selectedFile = null;
        document.getElementById('save-avatar-btn').disabled = true;
    }
    
    /**
     * Abrir modal
     */
    function openAvatarModal() {
        const modal = document.getElementById('avatar-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Cargar foto actual en preview
        const currentPhoto = document.querySelector('.user-avatar img, .profile-photo');
        if (currentPhoto) {
            document.getElementById('avatar-preview-img').src = currentPhoto.src;
        }
    }
    
    /**
     * Cerrar modal
     */
    function closeAvatarModal() {
        const modal = document.getElementById('avatar-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Reset
        selectedAvatar = null;
        selectedFile = null;
        removeUpload();
        switchTab('predefined');
    }
    
    /**
     * Mostrar error
     */
    function showError(message) {
        // Usar el sistema de notificaciones existente o crear uno simple
        alert(message);
    }
    
    /**
     * Mostrar éxito
     */
    function showSuccess(message) {
        // Usar el sistema de notificaciones existente
        alert(message);
    }
    
    /**
     * Agregar estilos CSS
     */
    function addAvatarStyles() {
        const styles = document.createElement('style');
        styles.textContent = `
            .avatar-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 10000;
                justify-content: center;
                align-items: center;
                animation: fadeIn 0.3s;
            }
            
            .avatar-modal-content {
                background: #1a1a2e;
                border-radius: 20px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow: hidden;
                animation: slideUp 0.3s;
            }
            
            .avatar-modal-header {
                padding: 20px 30px;
                background: linear-gradient(135deg, #ff8a00, #e52e71);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .avatar-modal-header h2 {
                margin: 0;
                color: white;
                font-size: 1.5rem;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .avatar-modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 5px 10px;
                border-radius: 50%;
                transition: all 0.3s;
            }
            
            .avatar-modal-close:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: rotate(90deg);
            }
            
            .avatar-modal-body {
                padding: 30px;
                max-height: calc(90vh - 180px);
                overflow-y: auto;
            }
            
            .avatar-preview-section {
                text-align: center;
                margin-bottom: 20px;
            }
            
            .avatar-preview-current {
                width: 120px;
                height: 120px;
                border-radius: 50%;
                overflow: hidden;
                margin: 0 auto 10px;
                border: 4px solid #ff8a00;
                box-shadow: 0 4px 20px rgba(255, 138, 0, 0.3);
            }
            
            .avatar-preview-current img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .avatar-preview-label {
                color: #aaa;
                font-size: 0.9rem;
            }
            
            .avatar-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .avatar-tab {
                flex: 1;
                padding: 12px;
                background: #16213e;
                border: 2px solid transparent;
                border-radius: 10px;
                color: white;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            
            .avatar-tab:hover {
                background: #1f2f50;
            }
            
            .avatar-tab.active {
                background: #ff8a00;
                border-color: #ff8a00;
            }
            
            .avatar-tab-content {
                display: none;
            }
            
            .avatar-tab-content.active {
                display: block;
            }
            
            .avatar-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 15px;
            }
            
            .avatar-option {
                position: relative;
                aspect-ratio: 1;
                border-radius: 15px;
                overflow: hidden;
                cursor: pointer;
                border: 3px solid transparent;
                transition: all 0.3s;
            }
            
            .avatar-option:hover {
                transform: scale(1.05);
                border-color: #ff8a00;
            }
            
            .avatar-option.selected {
                border-color: #ff8a00;
                box-shadow: 0 0 20px rgba(255, 138, 0, 0.5);
            }
            
            .avatar-option img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .avatar-option-check {
                position: absolute;
                top: 5px;
                right: 5px;
                background: #ff8a00;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: none;
                align-items: center;
                justify-content: center;
                color: white;
            }
            
            .avatar-option.selected .avatar-option-check {
                display: flex;
            }
            
            .avatar-upload-zone {
                border: 3px dashed #444;
                border-radius: 15px;
                padding: 60px 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                color: #aaa;
            }
            
            .avatar-upload-zone:hover,
            .avatar-upload-zone.dragover {
                border-color: #ff8a00;
                background: rgba(255, 138, 0, 0.1);
            }
            
            .avatar-upload-zone i {
                font-size: 4rem;
                color: #ff8a00;
            }
            
            .avatar-upload-hint {
                font-size: 0.85rem;
                color: #666;
            }
            
            .avatar-upload-preview {
                position: relative;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .avatar-upload-preview img {
                width: 100%;
                border-radius: 15px;
            }
            
            .avatar-remove-upload {
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .avatar-remove-upload:hover {
                background: #e74c3c;
            }
            
            .avatar-modal-footer {
                padding: 20px 30px;
                background: #0f0f1e;
                display: flex;
                justify-content: flex-end;
                gap: 15px;
            }
            
            .avatar-modal-footer button {
                padding: 12px 30px;
                border: none;
                border-radius: 10px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .btn-cancel {
                background: #444;
                color: white;
            }
            
            .btn-cancel:hover {
                background: #555;
            }
            
            .btn-save {
                background: #ff8a00;
                color: white;
            }
            
            .btn-save:hover:not(:disabled) {
                background: #ff6b00;
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(255, 138, 0, 0.4);
            }
            
            .btn-save:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            .avatar-loading {
                text-align: center;
                padding: 40px;
                color: #aaa;
            }
            
            .avatar-loading i {
                font-size: 3rem;
                color: #ff8a00;
                margin-bottom: 15px;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Exportar funciones globales
    window.AvatarSelector = {
        open: openAvatarModal,
        close: closeAvatarModal
    };
    
    console.log('✅ Sistema de avatares cargado');
})();