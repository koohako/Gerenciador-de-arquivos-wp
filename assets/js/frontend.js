// Gerenciador de Arquivos Pro - Frontend JavaScript com Paginação
jQuery(document).ready(function($) {
    if (window.gap_ajax && gap_ajax.debug) console.log('🚀 Frontend JS carregado - versão com paginação');
    
    $('.gap-frontend-container').each(function() {
        const container = $(this);
        const baseFolder = container.data('base-folder') || '';
        let currentPath = '';
        let currentPage = 1;
    let itemsPerPage = 10; // Novo padrão frontend
    let allFiles = [];
    let totalFiles = 0;
    const GAP_FILE_NAME_MAX = 50; // Limite configurável para truncagem (frontend)
        
    if (window.gap_ajax && gap_ajax.debug) console.log('📁 Inicializando container:', container, 'Base folder:', baseFolder);
        
        init();
        
        function init() {
            loadFiles();
            bindEvents();
        }
        
        function bindEvents() {
            // Back button na navegação (só se existir)
            const backBtn = container.find('.gap-back-btn');
            if (backBtn.length > 0) {
                backBtn.on('click', navigateBack);
            }
            
            // Back button no cabeçalho (só se existir)
            const headerBackBtn = container.find('.gap-header-back-btn');
            if (headerBackBtn.length > 0) {
                headerBackBtn.on('click', navigateBack);
            }
            
            // File item clicks
            container.on('click', '.gap-file-item[data-type="folder"]', function() {
                navigateToFolder($(this).data('name'));
            });
            
            // File downloads
            container.on('click', '.gap-download-btn', function(e) {
                e.stopPropagation();
                const fileItem = $(this).closest('.gap-file-item');
                downloadFile(fileItem);
            });
        }
        
        function loadFiles() {
            const content = container.find('.gap-frontend-content');
            content.html(`
                <div class="gap-loading">
                    <div class="gap-loading-spinner"></div>
                    <div class="gap-loading-text">Carregando arquivos...</div>
                </div>
            `);
            
            // Constroi o caminho completo
            const fullPath = baseFolder ? (currentPath ? baseFolder + '/' + currentPath : baseFolder) : currentPath;
            
            $.ajax({
                url: gap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gap_list_files',
                    nonce: gap_ajax.nonce,
                    path: fullPath
                },
                success: function(response) {
                    if (window.gap_ajax && gap_ajax.debug) console.log('📥 Resposta AJAX recebida:', response);
                    if (response.success) {
                        allFiles = response.data.items || [];
                        totalFiles = allFiles.length;
                        currentPage = 1;
                        if (window.gap_ajax && gap_ajax.debug) console.log('📊 Total de arquivos:', totalFiles, 'Arquivos:', allFiles);
                        displayCurrentPage();
                        updateNavigation();
                        updatePagination();
                    } else {
                        if (window.gap_ajax && gap_ajax.debug) console.error('❌ Erro na resposta:', response.data);
                        showError('Erro ao carregar arquivos: ' + response.data);
                    }
                },
                error: function() {
                    showError('Erro de conexão');
                }
            });
        }
        
        function displayCurrentPage() {
            if (window.gap_ajax && gap_ajax.debug) console.log('📄 Exibindo página:', currentPage, 'de', Math.ceil(totalFiles / itemsPerPage));
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const pageFiles = allFiles.slice(startIndex, endIndex);
            if (window.gap_ajax && gap_ajax.debug) console.log('🔢 Índices:', startIndex, '-', endIndex, 'Arquivos da página:', pageFiles);
            
            displayFiles(pageFiles);
        }
        
        function displayFiles(files) {
            const content = container.find('.gap-frontend-content');
            
            if (allFiles.length === 0) {
                content.html(`
                    <div class="gap-empty-state">
                        <div class="gap-empty-icon">📁</div>
                        <div class="gap-empty-title">Pasta vazia</div>
                        <div class="gap-empty-description">Não há arquivos ou pastas para exibir.</div>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="gap-file-grid">';
            
            function shortenFileName(name, max = GAP_FILE_NAME_MAX) {
                if (name.length <= max) return name;
                const extIndex = name.lastIndexOf('.');
                if (extIndex > 0 && extIndex < name.length - 1) {
                    const ext = name.substring(extIndex + 1);
                    const base = name.substring(0, extIndex);
                    const keep = max - ext.length - 4; // ... + . + ext
                    if (keep > 3) {
                        return base.substring(0, keep) + '...' + '.' + ext;
                    }
                }
                return name.substring(0, max - 3) + '...';
            }

            files.forEach(function(file) {
                const icon = getFileIcon(file);
                const typeClass = getFileTypeClass(file);
                const shortName = shortenFileName(file.name);
                
                if (file.type === 'folder') {
                    html += `
                        <div class="gap-file-item folder" data-name="${file.name}" data-type="folder">
                            <div class="gap-file-icon ${typeClass}">${icon}</div>
                            <div class="gap-file-info">
                                <div class="gap-file-name" title="${file.name}">${shortName}</div>
                                <div class="gap-file-meta">
                                    <span class="gap-file-date">${file.modified}</span>
                                    <span style="color: #6c757d;">📁 Pasta</span>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="gap-file-item file" data-name="${file.name}" data-type="file" data-path="${file.path || ''}">
                            ${file.is_image && file.url ? 
                                `<img src="${file.url}" alt="${file.name}" class="gap-image-preview">` : 
                                `<div class="gap-file-icon ${typeClass}">${icon}</div>`
                            }
                            <div class="gap-file-info">
                                <div class="gap-file-name" title="${file.name}">${shortName}</div>
                                <div class="gap-file-meta">
                                    ${file.size ? `<span class="gap-file-size">${file.size}</span>` : ''}
                                    <span class="gap-file-date">${file.modified}</span>
                                </div>
                            </div>
                            <div class="gap-file-actions">
                                <button class="gap-download-btn" title="Download">⬇️ Download</button>
                            </div>
                        </div>
                    `;
                }
            });
            
            html += '</div>';
            content.html(html);
        }
        
        function updatePagination() {
            // Remove paginação existente
            container.find('.gap-pagination').remove();
            
            // Exibe paginação a partir de 10 itens
            if (totalFiles < 10) {
                return; // Não precisa de paginação se menos de 10
            }
            
            const totalPages = Math.ceil(totalFiles / itemsPerPage);
            let paginationHtml = '<div class="gap-pagination">';
            
            // Botão Anterior
            paginationHtml += `<button class="gap-pagination-btn ${currentPage === 1 ? 'disabled' : ''}" 
                              onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
            
            // Páginas
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
            
            // Ajusta o início se estamos próximos do final
            if (endPage - startPage + 1 < maxVisiblePages) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }
            
            // Primeira página se não estiver visível
            if (startPage > 1) {
                paginationHtml += `<button class="gap-pagination-btn" onclick="changePage(1)">1</button>`;
                if (startPage > 2) {
                    paginationHtml += '<span class="gap-pagination-ellipsis">...</span>';
                }
            }
            
            // Páginas visíveis
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `<button class="gap-pagination-btn ${i === currentPage ? 'active' : ''}" 
                                  onclick="changePage(${i})">${i}</button>`;
            }
            
            // Última página se não estiver visível
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += '<span class="gap-pagination-ellipsis">...</span>';
                }
                paginationHtml += `<button class="gap-pagination-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
            }
            
            // Botão Próxima
            paginationHtml += `<button class="gap-pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" 
                              onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;
            
            // Info da página
            const startItem = (currentPage - 1) * itemsPerPage + 1;
            const endItem = Math.min(currentPage * itemsPerPage, totalFiles);
            paginationHtml += `<span class="gap-pagination-info">${startItem}-${endItem} de ${totalFiles}</span>`;
            
            // Items por página
            paginationHtml += `
                <div class="gap-items-per-page">
                    Mostrar: 
                    <select onchange="changeItemsPerPage(this.value)">
                        <option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
                        <option value="20" ${itemsPerPage === 20 ? 'selected' : ''}>20</option>
                        <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
                        <option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100</option>
                    </select>
                </div>
            `;
            
            paginationHtml += '</div>';
            
            // Adiciona a paginação após o conteúdo
            container.find('.gap-frontend-content').after(paginationHtml);
        }
        
        // Função global para mudança de página
        window.changePage = function(page) {
            const totalPages = Math.ceil(totalFiles / itemsPerPage);
            if (page >= 1 && page <= totalPages && page !== currentPage) {
                currentPage = page;
                displayCurrentPage();
                updatePagination();
            }
        };
        
        // Função global para mudança de itens por página
        window.changeItemsPerPage = function(newItemsPerPage) {
            itemsPerPage = parseInt(newItemsPerPage);
            currentPage = 1;
            displayCurrentPage();
            updatePagination();
        };
        
        function getFileIcon(file) {
            if (file.type === 'folder') {
                return '📁';
            }
            
            const ext = file.extension ? file.extension.toLowerCase() : '';
            
            const iconMap = {
                // Images
                'jpg': '🖼️', 'jpeg': '🖼️', 'png': '🖼️', 'gif': '🖼️', 
                'webp': '🖼️', 'svg': '🖼️', 'bmp': '🖼️',
                
                // Documents
                'pdf': '📕', 'doc': '📘', 'docx': '📘', 'txt': '📄', 
                'rtf': '📄', 'odt': '📘',
                
                // Spreadsheets
                'xls': '📊', 'xlsx': '📊', 'ods': '📊', 'csv': '📊',
                
                // Presentations
                'ppt': '📊', 'pptx': '📊', 'odp': '📊',
                
                // Videos
                'mp4': '🎬', 'avi': '🎬', 'mov': '🎬', 'wmv': '🎬', 
                'flv': '🎬', 'mkv': '🎬', 'webm': '🎬',
                
                // Audio
                'mp3': '🎵', 'wav': '🎵', 'flac': '🎵', 'aac': '🎵', 
                'ogg': '🎵', 'wma': '🎵',
                
                // Archives
                'zip': '🗂️', 'rar': '🗂️', '7z': '🗂️', 'tar': '🗂️', 
                'gz': '🗂️', 'bz2': '🗂️',
                
                // Code
                'js': '📜', 'css': '🎨', 'html': '🌐', 'php': '⚡', 
                'py': '🐍', 'java': '☕', 'cpp': '⚙️', 'c': '⚙️',
                'json': '📋', 'xml': '📋', 'yaml': '📋', 'yml': '📋'
            };
            
            return iconMap[ext] || '📄';
        }
        
        function getFileTypeClass(file) {
            if (file.type === 'folder') return 'folder';
            
            const ext = file.extension ? file.extension.toLowerCase() : '';
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) {
                return 'image';
            } else if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'].includes(ext)) {
                return 'video';
            } else if (['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma'].includes(ext)) {
                return 'audio';
            } else if (['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'].includes(ext)) {
                return 'document';
            }
            
            return 'file';
        }
        
        function updateNavigation() {
            const pathDisplay = container.find('.gap-current-path');
            const backBtn = container.find('.gap-back-btn');
            const headerBackBtn = container.find('.gap-header-back-btn');
            
            // Se elementos da navegação não existem mas existe botão no header, atualizar apenas o header
            const hasNavigation = pathDisplay.length > 0 && backBtn.length > 0;
            const hasHeaderBack = headerBackBtn.length > 0;
            
            // Atualizar navegação normal se existir
            if (hasNavigation) {
                // Mostra o caminho completo incluindo a pasta base
                let displayPath = '/';
                if (baseFolder && currentPath) {
                    displayPath = '/' + baseFolder + '/' + currentPath;
                } else if (baseFolder) {
                    displayPath = '/' + baseFolder;
                } else if (currentPath) {
                    displayPath = '/' + currentPath;
                }
                
                pathDisplay.text(displayPath);
                
                if (currentPath) {
                    backBtn.prop('disabled', false).show();
                } else {
                    backBtn.prop('disabled', true).hide();
                }
            }
            
            // Atualizar botão do cabeçalho se existir
            if (hasHeaderBack) {
                if (currentPath) {
                    // Mostra e ativa o botão quando há caminho para voltar
                    headerBackBtn.css({
                                  'display': 'flex',
                                  'cursor': 'pointer',
                                  'background': 'rgba(255,255,255,0.3)',
                                  'border-color': 'rgba(255,255,255,0.5)'
                              })
                              .prop('disabled', false)
                              .show();
                } else {
                    // Oculta o botão quando está na raiz
                    headerBackBtn.css('display', 'none').hide();
                }
            }
        }
        
        function navigateToFolder(folderName) {
            currentPath = currentPath ? currentPath + '/' + folderName : folderName;
            loadFiles(); // Carrega a nova pasta e reseta a paginação
            updateNavigation(); // Atualiza a navegação após entrar na pasta
        }
        
        function navigateBack() {
            if (!currentPath) return;
            
            const pathParts = currentPath.split('/');
            pathParts.pop();
            currentPath = pathParts.join('/');
            loadFiles(); // Carrega a pasta anterior e reseta a paginação
            updateNavigation(); // Atualiza a navegação após voltar
        }
        
        function downloadFile(fileItem) {
            const fileName = fileItem.data('name');
            const fullPath = baseFolder ? (currentPath ? baseFolder + '/' + currentPath : baseFolder) : currentPath;
            const filePath = fullPath ? fullPath + '/' + fileName : fileName;
            
            // Show loading state
            const downloadBtn = fileItem.find('.gap-download-btn');
            const originalText = downloadBtn.html();
            downloadBtn.html('⏳ Baixando...').prop('disabled', true);
            
            // Tenta primeiro o download direto via URL
            $.ajax({
                url: gap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gap_get_file_info',
                    nonce: gap_ajax.nonce,
                    path: filePath
                },
                success: function(response) {
                    downloadBtn.html(originalText).prop('disabled', false);
                    
                    if (response.success && response.data.url) {
                        // Primeiro tenta o download direto
                        const link = document.createElement('a');
                        link.href = response.data.url;
                        link.download = fileName;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        // Como fallback, também oferece o download seguro
                        setTimeout(function() {
                            const secureDownloadUrl = window.location.origin + window.location.pathname + 
                                '?gap_download=1&file=' + encodeURIComponent(filePath) + 
                                '&nonce=' + encodeURIComponent(gap_ajax.nonce);
                            
                            // Cria um link invisível para backup
                            const backupLink = document.createElement('a');
                            backupLink.href = secureDownloadUrl;
                            backupLink.style.display = 'none';
                            backupLink.target = '_blank';
                            document.body.appendChild(backupLink);
                            document.body.removeChild(backupLink);
                        }, 500);
                    } else {
                        // Se falhou, usa o download seguro
                        const secureDownloadUrl = window.location.origin + window.location.pathname + 
                            '?gap_download=1&file=' + encodeURIComponent(filePath) + 
                            '&nonce=' + encodeURIComponent(gap_ajax.nonce);
                        
                        window.open(secureDownloadUrl, '_blank');
                    }
                },
                error: function() {
                    downloadBtn.html(originalText).prop('disabled', false);
                    
                    // Como último recurso, tenta o download seguro
                    const secureDownloadUrl = window.location.origin + window.location.pathname + 
                        '?gap_download=1&file=' + encodeURIComponent(filePath) + 
                        '&nonce=' + encodeURIComponent(gap_ajax.nonce);
                    
                    window.open(secureDownloadUrl, '_blank');
                }
            });
        }
        
        function showError(message) {
            const content = container.find('.gap-frontend-content');
            content.html(`
                <div class="gap-error">
                    <div class="gap-error-icon">⚠️</div>
                    <div class="gap-error-message">${message}</div>
                </div>
            `);
        }
    });
});
