/**
 * music-uploader.js - Sistema de subida y gestión de música
 * frontend/assets/js/music-uploader.js
 */

(function() {
    'use strict';
    
    const API_BASE = '/red-social/backend/api/music.php';
    
    // Estado global
    let selectedAudio = null;
    let selectedCover = null;
    let uploadedSongId = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎵 Sistema de música inicializado');
        initMusicUploader();
    });
    
    /**
     * Inicializar sistema de música
     */
    function initMusicUploader() {
        // Crear modal de subida de música
        createMusicModal();
        
        // Event listener para botón de música en crear post
        const musicBtn = document.querySelector('.create-post .action-btn:nth-child(2)');
        if (musicBtn) {
            musicBtn.addEventListener('click', openMusicModal);
        }
    }
    
    /**
     * Crear modal de subida de música
     */
    function createMusicModal() {
        const modalHTML = `
            <div id="music-modal" class="music-modal">
                <div class="music-modal-content">
                    <div class="music-modal-header">
                        <h2><i class="fas fa-music"></i> Subir Música</h2>
                        <button class="music-modal-close" id="close-music-modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="music-modal-body">
                        <form id="music-upload-form" enctype="multipart/form-data">
                            <!-- Subir archivo de audio -->
                            <div class="upload-section">
                                <label class="upload-label">
                                    <i class="fas fa-file-audio"></i> Archivo de Audio (MP3)
                                </label>
                                <div class="upload-zone" id="audio-upload-zone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Arrastra tu archivo MP3 aquí o haz click</p>
                                    <p class="upload-hint">Máximo 10MB</p>
                                    <input type="file" id="audio-file-input" name="audio" accept="audio/mp3,audio/mpeg" hidden>
                                </div>
                                <div class="file-preview" id="audio-preview" style="display: none;">
                                    <i class="fas fa-file-audio"></i>
                                    <span class="file-name"></span>
                                    <span class="file-size"></span>
                                    <button type="button" class="remove-file" data-target="audio">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Información de la canción -->
                            <div class="form-group">
                                <label>Título de la canción *</label>
                                <input type="text" name="titulo" id="song-title" placeholder="Ej: Mi nueva canción" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Artista *</label>
                                <input type="text" name="artista" id="song-artist" placeholder="Ej: Tu nombre" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Género</label>
                                <select name="genero" id="song-genre">
                                    <option value="">Selecciona un género</option>
                                    <option value="Rock">Rock</option>
                                    <option value="Pop">Pop</option>
                                    <option value="Hip Hop">Hip Hop</option>
                                    <option value="Electrónica">Electrónica</option>
                                    <option value="Jazz">Jazz</option>
                                    <option value="Reggaeton">Reggaeton</option>
                                    <option value="Indie">Indie</option>
                                    <option value="R&B">R&B</option>
                                    <option value="Trap">Trap</option>
                                    <option value="Alternativo">Alternativo</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea name="descripcion" id="song-description" rows="3" placeholder="Describe tu canción..."></textarea>
                            </div>
                            
                            <!-- Subir portada -->
                            <div class="upload-section">
                                <label class="upload-label">
                                    <i class="fas fa-image"></i> Portada (Opcional)
                                </label>
                                <div class="upload-zone small" id="cover-upload-zone">
                                    <i class="fas fa-image"></i>
                                    <p>Imagen de portada</p>
                                    <input type="file" id="cover-file-input" name="portada" accept="image/*" hidden>
                                </div>
                                <div class="cover-preview" id="cover-preview" style="display: none;">
                                    <img id="cover-preview-img" src="" alt="Portada">
                                    <button type="button" class="remove-file" data-target="cover">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Progress bar -->
                            <div class="upload-progress" id="upload-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progress-fill"></div>
                                </div>
                                <div class="progress-text" id="progress-text">Subiendo... 0%</div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="music-modal-footer">
                        <button type="button" class="btn-cancel" id="cancel-music-btn">Cancelar</button>
                        <button type="button" class="btn-upload" id="upload-music-btn" disabled>
                            <i class="fas fa-upload"></i> Subir Canción
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        addMusicStyles();
        setupMusicModalEvents();
    }
    
    /**
     * Setup event listeners del modal
     */
    function setupMusicModalEvents() {
        // Cerrar modal
        document.getElementById('close-music-modal')?.addEventListener('click', closeMusicModal);
        document.getElementById('cancel-music-btn')?.addEventListener('click', closeMusicModal);
        
        // Click fuera del modal
        document.getElementById('music-modal')?.addEventListener('click', function(e) {
            if (e.target === this) closeMusicModal();
        });
        
        // Upload zones
        const audioZone = document.getElementById('audio-upload-zone');
        const audioInput = document.getElementById('audio-file-input');
        const coverZone = document.getElementById('cover-upload-zone');
        const coverInput = document.getElementById('cover-file-input');
        
        audioZone?.addEventListener('click', () => audioInput?.click());
        coverZone?.addEventListener('click', () => coverInput?.click());
        
        // File inputs
        audioInput?.addEventListener('change', (e) => handleAudioSelect(e.target.files[0]));
        coverInput?.addEventListener('change', (e) => handleCoverSelect(e.target.files[0]));
        
        // Drag & drop para audio
        setupDragDrop(audioZone, handleAudioSelect);
        setupDragDrop(coverZone, handleCoverSelect);
        
        // Remove buttons
        document.querySelectorAll('.remove-file').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = this.dataset.target;
                if (target === 'audio') removeAudio();
                if (target === 'cover') removeCover();
            });
        });
        
        // Upload button
        document.getElementById('upload-music-btn')?.addEventListener('click', uploadMusic);
    }
    
    /**
     * Setup drag & drop
     */
    function setupDragDrop(zone, handler) {
        if (!zone) return;
        
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        
        zone.addEventListener('dragleave', () => {
            zone.classList.remove('dragover');
        });
        
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                handler(e.dataTransfer.files[0]);
            }
        });
    }
    
    /**
     * Manejar selección de audio
     */
    function handleAudioSelect(file) {
        if (!file) return;
        
        // Validar tipo
        if (!file.type.match('audio/mpeg') && !file.type.match('audio/mp3')) {
            showNotification('Solo se permiten archivos MP3', 'error');
            return;
        }
        
        // Validar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
            showNotification('El archivo no puede superar 10MB', 'error');
            return;
        }
        
        selectedAudio = file;
        
        // Mostrar preview
        document.getElementById('audio-upload-zone').style.display = 'none';
        const preview = document.getElementById('audio-preview');
        preview.style.display = 'flex';
        preview.querySelector('.file-name').textContent = file.name;
        preview.querySelector('.file-size').textContent = formatFileSize(file.size);
        
        // Habilitar botón de subida si hay título y artista
        checkFormValid();
        
        // Auto-llenar título desde nombre de archivo
        const titleInput = document.getElementById('song-title');
        if (!titleInput.value) {
            titleInput.value = file.name.replace(/\.[^/.]+$/, '').replace(/_/g, ' ');
        }
    }
    
    /**
     * Manejar selección de portada
     */
    function handleCoverSelect(file) {
        if (!file) return;
        
        // Validar tipo
        if (!file.type.match('image.*')) {
            showNotification('Solo se permiten imágenes', 'error');
            return;
        }
        
        // Validar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('La imagen no puede superar 5MB', 'error');
            return;
        }
        
        selectedCover = file;
        
        // Preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('cover-upload-zone').style.display = 'none';
            const preview = document.getElementById('cover-preview');
            preview.style.display = 'block';
            preview.querySelector('img').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    /**
     * Remover audio seleccionado
     */
    function removeAudio() {
        selectedAudio = null;
        document.getElementById('audio-file-input').value = '';
        document.getElementById('audio-upload-zone').style.display = 'flex';
        document.getElementById('audio-preview').style.display = 'none';
        checkFormValid();
    }
    
    /**
     * Remover portada seleccionada
     */
    function removeCover() {
        selectedCover = null;
        document.getElementById('cover-file-input').value = '';
        document.getElementById('cover-upload-zone').style.display = 'flex';
        document.getElementById('cover-preview').style.display = 'none';
    }
    
    /**
     * Verificar si el formulario es válido
     */
    function checkFormValid() {
        const titulo = document.getElementById('song-title').value.trim();
        const artista = document.getElementById('song-artist').value.trim();
        const uploadBtn = document.getElementById('upload-music-btn');
        
        uploadBtn.disabled = !(selectedAudio && titulo && artista);
    }
    
    // Event listeners para validación en tiempo real
    document.addEventListener('DOMContentLoaded', function() {
        ['song-title', 'song-artist'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', checkFormValid);
        });
    });
    
    /**
     * Subir música
     */
    async function uploadMusic() {
        if (!selectedAudio) {
            showNotification('Selecciona un archivo de audio', 'error');
            return;
        }
        
        const titulo = document.getElementById('song-title').value.trim();
        const artista = document.getElementById('song-artist').value.trim();
        
        if (!titulo || !artista) {
            showNotification('Título y artista son requeridos', 'error');
            return;
        }
        
        const uploadBtn = document.getElementById('upload-music-btn');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
        
        // Mostrar barra de progreso
        const progressDiv = document.getElementById('upload-progress');
        progressDiv.style.display = 'block';
        
        try {
            // Crear FormData
            const formData = new FormData();
            formData.append('audio', selectedAudio);
            formData.append('titulo', titulo);
            formData.append('artista', artista);
            formData.append('genero', document.getElementById('song-genre').value);
            formData.append('descripcion', document.getElementById('song-description').value);
            
            if (selectedCover) {
                formData.append('portada', selectedCover);
            }
            
            // Subir con progress
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    document.getElementById('progress-fill').style.width = percent + '%';
                    document.getElementById('progress-text').textContent = `Subiendo... ${percent}%`;
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    const result = JSON.parse(xhr.responseText);
                    
                    if (result.success) {
                        uploadedSongId = result.data.song_id;
                        showNotification('¡Canción subida exitosamente!', 'success');
                        
                        // Insertar en el textarea del post
                        insertSongInPost(result.data);
                        
                        // Cerrar modal después de 1 segundo
                        setTimeout(() => {
                            closeMusicModal();
                            resetForm();
                        }, 1000);
                    } else {
                        showNotification(result.message, 'error');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Subir Canción';
                    }
                } else {
                    showNotification('Error al subir la canción', 'error');
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Subir Canción';
                }
                
                progressDiv.style.display = 'none';
            });
            
            xhr.addEventListener('error', function() {
                showNotification('Error de conexión', 'error');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Subir Canción';
                progressDiv.style.display = 'none';
            });
            
            xhr.open('POST', `${API_BASE}?action=upload`);
            xhr.send(formData);
            
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error al subir la canción', 'error');
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Subir Canción';
            progressDiv.style.display = 'none';
        }
    }
    
    /**
     * Insertar canción en el post
     */
    function insertSongInPost(songData) {
        const postInput = document.querySelector('.create-post .post-input');
        if (!postInput) return;
        
        // Guardar ID de canción en un atributo data
        postInput.dataset.songId = songData.song_id;
        
        // Mostrar preview debajo del textarea
        let preview = document.querySelector('.music-post-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'music-post-preview';
            postInput.parentElement.appendChild(preview);
        }
        
        preview.innerHTML = `
            <div class="attached-song">
                <i class="fas fa-music"></i>
                <div class="song-info">
                    <div class="song-title">${songData.titulo || 'Sin título'}</div>
                    <div class="song-artist">${songData.artista || 'Desconocido'}</div>
                    <div class="song-duration">${songData.duracion_formateada || '--:--'}</div>
                </div>
                <button class="remove-attached-song" onclick="window.MusicUploader.removeAttachedSong()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        preview.style.display = 'block';
    }
    
    /**
     * Remover canción adjunta del post
     */
    function removeAttachedSong() {
        const postInput = document.querySelector('.create-post .post-input');
        if (postInput) {
            delete postInput.dataset.songId;
        }
        
        const preview = document.querySelector('.music-post-preview');
        if (preview) {
            preview.style.display = 'none';
            preview.innerHTML = '';
        }
        
        uploadedSongId = null;
    }
    
    /**
     * Abrir modal
     */
    function openMusicModal() {
        document.getElementById('music-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Cerrar modal
     */
    function closeMusicModal() {
        document.getElementById('music-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    
    /**
     * Resetear formulario
     */
    function resetForm() {
        document.getElementById('music-upload-form').reset();
        removeAudio();
        removeCover();
        selectedAudio = null;
        selectedCover = null;
    }
    
    /**
     * Formatear tamaño de archivo
     */
    function formatFileSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }
    
    /**
     * Mostrar notificación
     */
    function showNotification(message, type = 'info') {
        // Reusar sistema de notificaciones existente o crear uno simple
        alert(message);
    }
    
    /**
     * Agregar estilos CSS
     */
    function addMusicStyles() {
        const styles = document.createElement('style');
        styles.textContent = `
            .music-modal {
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
                overflow-y: auto;
            }
            
            .music-modal-content {
                background: #1a1a2e;
                border-radius: 20px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
                margin: 20px;
            }
            
            .music-modal-header {
                padding: 20px 30px;
                background: linear-gradient(135deg, #ff8a00, #e52e71);
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 20px 20px 0 0;
            }
            
            .music-modal-header h2 {
                margin: 0;
                color: white;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .music-modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 5px 10px;
                border-radius: 50%;
                transition: all 0.3s;
            }
            
            .music-modal-close:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: rotate(90deg);
            }
            
            .music-modal-body {
                padding: 30px;
            }
            
            .upload-section {
                margin-bottom: 25px;
            }
            
            .upload-label {
                display: block;
                margin-bottom: 10px;
                color: #ff8a00;
                font-weight: 600;
            }
            
            .upload-zone {
                border: 3px dashed #444;
                border-radius: 15px;
                padding: 40px 20px;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s;
                color: #aaa;
            }
            
            .upload-zone.small {
                padding: 30px 20px;
            }
            
            .upload-zone:hover,
            .upload-zone.dragover {
                border-color: #ff8a00;
                background: rgba(255, 138, 0, 0.1);
            }
            
            .upload-zone i {
                font-size: 3rem;
                color: #ff8a00;
                margin-bottom: 10px;
            }
            
            .upload-hint {
                font-size: 0.85rem;
                color: #666;
                margin-top: 5px;
            }
            
            .file-preview {
                display: none;
                align-items: center;
                gap: 15px;
                padding: 15px;
                background: rgba(255, 138, 0, 0.1);
                border-radius: 10px;
                border: 2px solid #ff8a00;
            }
            
            .file-preview i {
                font-size: 2rem;
                color: #ff8a00;
            }
            
            .file-name {
                flex: 1;
                color: white;
                font-weight: 600;
            }
            
            .file-size {
                color: #aaa;
                font-size: 0.9rem;
            }
            
            .cover-preview {
                display: none;
                position: relative;
                max-width: 200px;
                margin: 10px auto;
            }
            
            .cover-preview img {
                width: 100%;
                border-radius: 10px;
            }
            
            .remove-file {
                background: #e74c3c;
                border: none;
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .remove-file:hover {
                background: #c0392b;
                transform: scale(1.1);
            }
            
            .cover-preview .remove-file {
                position: absolute;
                top: -10px;
                right: -10px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: white;
                font-weight: 600;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                width: 100%;
                padding: 12px;
                border: 2px solid #333;
                border-radius: 10px;
                background: #16213e;
                color: white;
                font-size: 1rem;
                transition: all 0.3s;
            }
            
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                outline: none;
                border-color: #ff8a00;
            }
            
            .upload-progress {
                margin-top: 20px;
            }
            
            .progress-bar {
                width: 100%;
                height: 30px;
                background: #16213e;
                border-radius: 15px;
                overflow: hidden;
                margin-bottom: 10px;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #ff8a00, #e52e71);
                width: 0%;
                transition: width 0.3s;
            }
            
            .progress-text {
                text-align: center;
                color: white;
                font-weight: 600;
            }
            
            .music-modal-footer {
                padding: 20px 30px;
                background: #0f0f1e;
                display: flex;
                justify-content: flex-end;
                gap: 15px;
                border-radius: 0 0 20px 20px;
            }
            
            .music-modal-footer button {
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
            
            .btn-upload {
                background: #ff8a00;
                color: white;
            }
            
            .btn-upload:hover:not(:disabled) {
                background: #ff6b00;
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(255, 138, 0, 0.4);
            }
            
            .btn-upload:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* Preview de canción en el post */
            .music-post-preview {
                display: none;
                margin-top: 15px;
                padding: 15px;
                background: rgba(255, 138, 0, 0.1);
                border-radius: 10px;
                border: 2px solid #ff8a00;
            }
            
            .attached-song {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .attached-song i {
                font-size: 2rem;
                color: #ff8a00;
            }
            
            .song-info {
                flex: 1;
            }
            
            .song-title {
                font-weight: 600;
                color: white;
                margin-bottom: 5px;
            }
            
            .song-artist {
                color: #aaa;
                font-size: 0.9rem;
            }
            
            .song-duration {
                color: #ff8a00;
                font-size: 0.85rem;
                margin-top: 3px;
            }
            
            .remove-attached-song {
                background: #e74c3c;
                border: none;
                color: white;
                width: 35px;
                height: 35px;
                border-radius: 50%;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .remove-attached-song:hover {
                background: #c0392b;
                transform: scale(1.1);
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Exportar funciones globales
    window.MusicUploader = {
        open: openMusicModal,
        close: closeMusicModal,
        removeAttachedSong: removeAttachedSong,
        getUploadedSongId: () => uploadedSongId
    };
    
    console.log('✅ Sistema de música cargado');
})();