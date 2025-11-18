/**
 * music-player.js - Reproductor de música personalizado
 * frontend/assets/js/music-player.js
 */

(function() {
    'use strict';
    
    const API_BASE = '/red-social/backend/api/music.php';
    let currentPlayer = null;
    let currentAudio = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎵 Reproductor de música inicializado');
        initMusicPlayers();
    });
    
    /**
     * Inicializar todos los reproductores en la página
     */
    function initMusicPlayers() {
        const players = document.querySelectorAll('.music-player');
        
        players.forEach(player => {
            setupPlayer(player);
        });
        
        // Observer para posts cargados dinámicamente
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        const newPlayers = node.querySelectorAll('.music-player');
                        newPlayers.forEach(setupPlayer);
                    }
                });
            });
        });
        
        const feed = document.querySelector('.feed');
        if (feed) {
            observer.observe(feed, { childList: true, subtree: true });
        }
    }
    
    /**
     * Configurar un reproductor individual
     */
    function setupPlayer(playerElement) {
        // Evitar inicializar dos veces
        if (playerElement.dataset.initialized) return;
        playerElement.dataset.initialized = 'true';
        
        const songId = playerElement.dataset.songId;
        const audioUrl = playerElement.dataset.audioUrl;
        
        if (!audioUrl) return;
        
        // Crear elemento de audio
        const audio = new Audio();
        audio.src = '/red-social/backend/' + audioUrl;
        audio.preload = 'metadata';
        
        // Elementos del reproductor
        const playBtn = playerElement.querySelector('.play-btn');
        const progressBar = playerElement.querySelector('.progress-bar');
        const progressFill = playerElement.querySelector('.progress-fill');
        const currentTimeEl = playerElement.querySelector('.current-time');
        const totalTimeEl = playerElement.querySelector('.total-time');
        const volumeBtn = playerElement.querySelector('.volume-btn');
        const volumeSlider = playerElement.querySelector('.volume-slider');
        const downloadBtn = playerElement.querySelector('.download-btn');
        
        // Estado
        let isPlaying = false;
        let isSeeking = false;
        let playRegistered = false;
        
        // Event: Play/Pause
        playBtn?.addEventListener('click', function() {
            if (isPlaying) {
                pauseAudio();
            } else {
                playAudio();
            }
        });
        
        // Event: Progress bar click
        progressBar?.addEventListener('click', function(e) {
            if (!audio.duration) return;
            
            const rect = progressBar.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            audio.currentTime = percent * audio.duration;
        });
        
        // Event: Progress bar drag
        progressBar?.addEventListener('mousedown', function(e) {
            isSeeking = true;
            updateProgress(e);
            
            const onMouseMove = (e) => updateProgress(e);
            const onMouseUp = () => {
                isSeeking = false;
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            };
            
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });
        
        function updateProgress(e) {
            if (!audio.duration) return;
            const rect = progressBar.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            audio.currentTime = percent * audio.duration;
        }
        
        // Event: Volume
        volumeBtn?.addEventListener('click', function() {
            if (audio.volume > 0) {
                audio.volume = 0;
                volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                if (volumeSlider) volumeSlider.value = 0;
            } else {
                audio.volume = 0.7;
                volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                if (volumeSlider) volumeSlider.value = 70;
            }
        });
        
        volumeSlider?.addEventListener('input', function() {
            const volume = this.value / 100;
            audio.volume = volume;
            
            if (volume === 0) {
                volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
            } else if (volume < 0.5) {
                volumeBtn.innerHTML = '<i class="fas fa-volume-down"></i>';
            } else {
                volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
            }
        });
        
        // Event: Download
        downloadBtn?.addEventListener('click', function() {
            const link = document.createElement('a');
            link.href = audio.src;
            link.download = playerElement.dataset.songTitle || 'cancion.mp3';
            link.click();
        });
        
        // Audio events
        audio.addEventListener('loadedmetadata', function() {
            if (totalTimeEl) {
                totalTimeEl.textContent = formatTime(audio.duration);
            }
        });
        
        audio.addEventListener('timeupdate', function() {
            if (!isSeeking) {
                const percent = (audio.currentTime / audio.duration) * 100;
                if (progressFill) {
                    progressFill.style.width = percent + '%';
                }
                if (currentTimeEl) {
                    currentTimeEl.textContent = formatTime(audio.currentTime);
                }
            }
            
            // Registrar reproducción cuando llega al 30%
            if (!playRegistered && audio.currentTime > audio.duration * 0.3) {
                playRegistered = true;
                registerPlay(songId);
            }
        });
        
        audio.addEventListener('ended', function() {
            pauseAudio();
            audio.currentTime = 0;
            playRegistered = false;
        });
        
        audio.addEventListener('error', function() {
            playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            playBtn.disabled = true;
            console.error('Error al cargar el audio');
        });
        
        // Funciones de control
        function playAudio() {
            // Pausar cualquier otro reproductor
            if (currentPlayer && currentPlayer !== playerElement) {
                const otherPlayBtn = currentPlayer.querySelector('.play-btn');
                if (otherPlayBtn) {
                    otherPlayBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
                if (currentAudio && !currentAudio.paused) {
                    currentAudio.pause();
                }
            }
            
            audio.play().then(() => {
                isPlaying = true;
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                playerElement.classList.add('playing');
                currentPlayer = playerElement;
                currentAudio = audio;
            }).catch(err => {
                console.error('Error al reproducir:', err);
            });
        }
        
        function pauseAudio() {
            audio.pause();
            isPlaying = false;
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
            playerElement.classList.remove('playing');
        }
        
        // Guardar referencia al audio en el elemento
        playerElement._audio = audio;
    }
    
    /**
     * Formatear tiempo en MM:SS
     */
    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    /**
     * Registrar reproducción en el servidor
     */
    async function registerPlay(songId) {
        if (!songId) return;
        
        try {
            await fetch(`${API_BASE}?action=play`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ song_id: parseInt(songId) })
            });
        } catch (error) {
            console.error('Error al registrar reproducción:', error);
        }
    }
    
    /**
     * Crear HTML de reproductor
     */
    function createPlayerHTML(songData) {
        const coverUrl = songData.portada_url 
            ? `/red-social/backend/${songData.portada_url}` 
            : '/red-social/frontend/assets/images/default-cover.jpg';
        
        return `
            <div class="music-player" 
                 data-song-id="${songData.id || ''}" 
                 data-audio-url="${songData.archivo_url || songData.cancion_url || ''}"
                 data-song-title="${songData.titulo || songData.cancion_nombre || 'Canción'}">
                
                <div class="player-cover">
                    <img src="${coverUrl}" alt="Portada">
                    <div class="cover-overlay">
                        <button class="play-btn">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </div>
                
                <div class="player-info">
                    <div class="song-title">${songData.titulo || songData.cancion_nombre || 'Sin título'}</div>
                    <div class="song-artist">${songData.artista || songData.cancion_artista || 'Desconocido'}</div>
                    
                    <div class="player-controls">
                        <div class="progress-container">
                            <span class="current-time">0:00</span>
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <span class="total-time">0:00</span>
                        </div>
                        
                        <div class="player-buttons">
                            <button class="control-btn volume-btn" title="Volumen">
                                <i class="fas fa-volume-up"></i>
                            </button>
                            <input type="range" class="volume-slider" min="0" max="100" value="70">
                            <button class="control-btn download-btn" title="Descargar">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    
                    ${songData.reproducciones ? `
                        <div class="player-stats">
                            <i class="fas fa-play-circle"></i> ${songData.reproducciones} reproducciones
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Agregar estilos CSS
    const styles = document.createElement('style');
    styles.textContent = `
        .music-player {
            display: flex;
            gap: 15px;
            background: rgba(255, 138, 0, 0.05);
            border: 2px solid rgba(255, 138, 0, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            transition: all 0.3s;
        }
        
        .music-player.playing {
            border-color: #ff8a00;
            box-shadow: 0 0 20px rgba(255, 138, 0, 0.3);
        }
        
        .player-cover {
            position: relative;
            width: 150px;
            height: 150px;
            flex-shrink: 0;
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .player-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .player-cover:hover .cover-overlay {
            opacity: 1;
        }
        
        .music-player.playing .cover-overlay {
            opacity: 1;
        }
        
        .play-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #ff8a00;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .play-btn:hover {
            background: #ff6b00;
            transform: scale(1.1);
        }
        
        .play-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .player-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .song-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            margin: 0;
        }
        
        .song-artist {
            font-size: 1rem;
            color: #aaa;
        }
        
        .player-controls {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .current-time,
        .total-time {
            font-size: 0.85rem;
            color: #aaa;
            min-width: 40px;
        }
        
        .progress-bar {
            flex: 1;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff8a00, #e52e71);
            width: 0%;
            transition: width 0.1s linear;
            border-radius: 3px;
        }
        
        .player-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: #aaa;
            font-size: 1.2rem;
            cursor: pointer;
            transition: color 0.3s;
            padding: 5px;
        }
        
        .control-btn:hover {
            color: #ff8a00;
        }
        
        .volume-slider {
            width: 80px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            outline: none;
            -webkit-appearance: none;
        }
        
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            background: #ff8a00;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .volume-slider::-moz-range-thumb {
            width: 12px;
            height: 12px;
            background: #ff8a00;
            border-radius: 50%;
            cursor: pointer;
            border: none;
        }
        
        .player-stats {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .player-stats i {
            color: #ff8a00;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .music-player {
                flex-direction: column;
            }
            
            .player-cover {
                width: 100%;
                height: 200px;
            }
            
            .volume-slider {
                width: 60px;
            }
        }
    `;
    document.head.appendChild(styles);
    
    // Exportar funciones
    window.MusicPlayer = {
        init: initMusicPlayers,
        createHTML: createPlayerHTML
    };
    
    console.log('✅ Reproductor de música cargado');
})();