// Gerenciador de Arquivos Pro - Admin JavaScript com Paginação
jQuery(document).ready(function($) {
    if (window.gap_ajax && gap_ajax.debug) console.log('🚀 Admin JS carregado - versão com paginação');
    
    let currentPath = '';
    let currentPage = 1;
    let itemsPerPage = 10; // Novo padrão: 10 itens por página
    let allFiles = [];
    let selectedItems = new Set();
    let totalFiles = 0;
    const GAP_FILE_NAME_MAX = 50; // Limite configurável para truncagem de nomes
    
    // Initialize
    init();
    
    function init() {
        loadFiles();
        bindEvents();
    }
    
    function bindEvents() {
        // Toggle select all
        $('#gap-select-toggle').on('click', function() {
            const totalOnPage = $('.gap-file-item').length;
            const selectedOnPage = $('.gap-file-item.selected').length;
            if (totalOnPage === 0) return;
            if (selectedOnPage === totalOnPage) {
                // Unselect all
                selectedItems.clear();
                $('.gap-file-item').removeClass('selected').find('.gap-select-checkbox').prop('checked', false);
            } else {
                $('.gap-file-item').each(function() {
                    const name = $(this).data('name');
                    selectedItems.add(name);
                    $(this).addClass('selected').find('.gap-select-checkbox').prop('checked', true);
                });
            }
            updateBulkButtons();
        });

        $('#gap-bulk-delete').on('click', function() {
            bulkDelete();
        });

        $('#gap-bulk-download').on('click', function() {
            bulkDownload();
        });

        // Upload button
        $('#gap-upload-btn').on('click', function() {
            $('#gap-upload-modal').show();
        });
        
        // New folder button
        $('#gap-new-folder-btn').on('click', function() {
            $('#gap-folder-modal').show();
        });
        
        // Back button
        $('#gap-back-btn').on('click', function() {
            navigateBack();
        });
        
        // Modal close buttons
        $('.gap-close').on('click', function() {
            $(this).closest('.gap-modal').hide();
            resetModals();
        });
        
        // Click outside modal to close
        $('.gap-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
                resetModals();
            }
        });
        
        // File input change
        $('#gap-file-input').on('change', function() {
            handleFileUpload(this.files);
        });
        
        // Drag and drop
        setupDragAndDrop();
        
        // Create folder
        $('#gap-create-folder').on('click', createFolder);
        
        // Enter key in folder name input
        $('#gap-folder-name').on('keypress', function(e) {
            if (e.which === 13) {
                createFolder();
            }
        });
        
        // File grid delegation
        $(document).on('click', '.gap-file-item[data-type="folder"]', function() {
            navigateToFolder($(this).data('name'));
        });
        
        $(document).on('click', '.gap-action-btn.delete', function(e) {
            e.stopPropagation();
            deleteItem($(this).closest('.gap-file-item'));
        });
        
        $(document).on('click', '.gap-action-btn.download', function(e) {
            e.stopPropagation();
            downloadFile($(this).closest('.gap-file-item'));
        });
    }
    
    function loadFiles() {
        $('#gap-file-grid').html('<div class="gap-loading"><span class="spinner is-active"></span> Carregando...</div>');
        
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gap_list_files',
                nonce: gap_ajax.nonce,
                path: currentPath
            },
            success: function(response) {
                if (window.gap_ajax && gap_ajax.debug) console.log('📥 Admin - Resposta AJAX:', response);
                if (response.success) {
                    allFiles = response.data.items || [];
                    totalFiles = allFiles.length;
                    currentPage = 1;
                    if (window.gap_ajax && gap_ajax.debug) console.log('📊 Admin - Total de arquivos:', totalFiles);
                    displayCurrentPage();
                    updatePath(response.data.current_path);
                    updatePagination();
                } else {
                    showError('Erro ao carregar arquivos: ' + response.data);
                }
            },
            error: function() {
                showError('Erro de conexão');
            }
        });
    }
    
    function displayCurrentPage() {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const pageFiles = allFiles.slice(startIndex, endIndex);
        
        displayFiles(pageFiles);
    }
    
    function displayFiles(files) {
        const grid = $('#gap-file-grid');
        
        if (allFiles.length === 0) {
            grid.html(`
                <div class="gap-empty-state">
                    <div class="dashicons dashicons-portfolio"></div>
                    <h3>Pasta vazia</h3>
                    <p>Não há arquivos ou pastas para exibir.</p>
                </div>
            `);
            return;
        }
        
        let html = '';
    function shortenFileName(name, max = GAP_FILE_NAME_MAX) {
            if (name.length <= max) return name;
            const extIndex = name.lastIndexOf('.');
            if (extIndex > 0 && extIndex < name.length - 1) {
                const ext = name.substring(extIndex + 1);
                const base = name.substring(0, extIndex);
                const keep = max - ext.length - 4; // espaço para ... + . + ext
                if (keep > 3) {
                    return base.substring(0, keep) + '...' + '.' + ext;
                }
            }
            return name.substring(0, max - 3) + '...';
        }

        files.forEach(function(file) {
            const icon = getFileIcon(file);
            const isImage = file.is_image && file.url;
            const shortName = shortenFileName(file.name);
            
            html += `
                <div class="gap-file-item ${file.type}" data-name="${file.name}" data-type="${file.type}">
                    <label class="gap-select"><input type="checkbox" class="gap-select-checkbox" data-name="${file.name}"></label>
                    <div class="gap-file-actions">
                        ${file.type === 'file' ? '<button class="gap-action-btn download" title="Download"><span class="dashicons dashicons-download"></span></button>' : ''}
                        <button class="gap-action-btn delete" title="Excluir"><span class="dashicons dashicons-trash"></span></button>
                    </div>
                    
                    ${isImage ? `<img src="${file.url}" alt="${file.name}" class="gap-image-preview" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 10px;">` : `<div class="gap-file-icon ${file.type} ${getFileTypeClass(file)}">${icon}</div>`}
                    
                    <div class="gap-file-name" title="${file.name}">${shortName}</div>
                    
                    <div class="gap-file-info">
                        ${file.size ? `<span class="gap-file-size">${file.size}</span>` : ''}
                        <span class="gap-file-date">${file.modified}</span>
                    </div>
                </div>
            `;
        });
        
        grid.html(html);

        // Reaplicar seleção existente
        $('.gap-file-item').each(function() {
            const name = $(this).data('name');
            if (selectedItems.has(name)) {
                $(this).addClass('selected').find('.gap-select-checkbox').prop('checked', true);
            }
        });

        // Checkbox events
        $('.gap-select-checkbox').off('click').on('click', function(e) {
            e.stopPropagation();
            const name = $(this).data('name');
            if (this.checked) {
                selectedItems.add(name);
                $(this).closest('.gap-file-item').addClass('selected');
            } else {
                selectedItems.delete(name);
                $(this).closest('.gap-file-item').removeClass('selected');
            }
            updateBulkButtons();
        });

        updateBulkButtons();
    }
    
    function getFileIcon(file) {
        if (file.type === 'folder') {
            return '<span class="dashicons dashicons-portfolio"></span>';
        }
        
        const ext = file.extension ? file.extension.toLowerCase() : '';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
            return '<span class="dashicons dashicons-format-image"></span>';
        } else if (['mp4', 'avi', 'mov', 'wmv', 'flv'].includes(ext)) {
            return '<span class="dashicons dashicons-format-video"></span>';
        } else if (['mp3', 'wav', 'flac', 'aac'].includes(ext)) {
            return '<span class="dashicons dashicons-format-audio"></span>';
        } else if (['pdf'].includes(ext)) {
            return '<span class="dashicons dashicons-pdf"></span>';
        } else if (['doc', 'docx', 'txt', 'rtf'].includes(ext)) {
            return '<span class="dashicons dashicons-text"></span>';
        } else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext)) {
            return '<span class="dashicons dashicons-archive"></span>';
        }
        
        return '<span class="dashicons dashicons-media-default"></span>';
    }
    
    function getFileTypeClass(file) {
        if (file.type === 'folder') return 'folder';
        
        const ext = file.extension ? file.extension.toLowerCase() : '';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
            return 'image';
        } else if (['mp4', 'avi', 'mov', 'wmv', 'flv'].includes(ext)) {
            return 'video';
        } else if (['mp3', 'wav', 'flac', 'aac'].includes(ext)) {
            return 'audio';
        } else if (['doc', 'docx', 'txt', 'rtf', 'pdf'].includes(ext)) {
            return 'document';
        }
        
        return 'file';
    }
    
    function updatePath(path) {
        currentPath = path;
        $('#gap-current-path').text('/' + (path || ''));
        
        if (path) {
            $('#gap-back-btn').show();
        } else {
            $('#gap-back-btn').hide();
        }
    }
    
    function navigateToFolder(folderName) {
        currentPath = currentPath ? currentPath + '/' + folderName : folderName;
        loadFiles();
    }
    
    function navigateBack() {
        const pathParts = currentPath.split('/');
        pathParts.pop();
        currentPath = pathParts.join('/');
        loadFiles();
    }
    
    function setupDragAndDrop() {
        const dropZone = $('.gap-drop-zone');
        
        dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });
        
        dropZone.on('dragleave dragend', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });
        
        dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            handleFileUpload(files);
        });
        
        dropZone.on('click', function() {
            $('#gap-file-input').click();
        });
    }
    
    function handleFileUpload(files) {
        if (!files || files.length === 0) return;
        
    if (window.gap_ajax && gap_ajax.debug) console.log('📤 Iniciando upload de', files.length, 'arquivo(s)');
        
        // Limites vindos do PHP via wp_localize_script
        const maxFiles = gap_ajax.max_file_uploads ? parseInt(gap_ajax.max_file_uploads) : 20;
        const maxSizePerFile = gap_ajax.max_upload_size ? parseInt(gap_ajax.max_upload_size) : (10 * 1024 * 1024);
        const postMaxSize = gap_ajax.post_max_size ? parseInt(gap_ajax.post_max_size) : (maxSizePerFile * 2);

        // Função local para formatar bytes (fallback se não existir global)
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

    if (window.gap_ajax && gap_ajax.debug) console.log('🔎 Limites detectados:', {
            maxFiles,
            maxSizePerFile: formatBytes(maxSizePerFile),
            postMaxSize: formatBytes(postMaxSize)
        });

        // Filtrar arquivos muito grandes (client-side)
        const validFiles = Array.from(files).filter(file => {
            if (file.size > maxSizePerFile) {
                showError(`Arquivo "${file.name}" excede o limite (${formatBytes(file.size)} > ${gap_ajax.max_upload_size_human || formatBytes(maxSizePerFile)})`);
                return false;
            }
            return true;
        });
        
        if (validFiles.length === 0) {
            showError('Nenhum arquivo válido para upload');
            return;
        }
        
        // Dividir em lotes se necessário
        const batchSize = Math.min(maxFiles || 20, 10); // Máximo 10 arquivos por lote para estabilidade
        const batches = [];
        
        for (let i = 0; i < validFiles.length; i += batchSize) {
            batches.push(validFiles.slice(i, i + batchSize));
        }
        
    if (window.gap_ajax && gap_ajax.debug) console.log('📦 Dividindo em', batches.length, 'lote(s)');
        
        // Mostrar progresso
        $('.gap-upload-progress').show();
        $('.gap-progress-fill').css('width', '0%');
        
        uploadBatches(batches, 0, validFiles.length);
    }
    
    function uploadBatches(batches, currentBatch, totalFiles) {
        if (currentBatch >= batches.length) {
            // Todos os lotes processados
            $('.gap-upload-progress').hide();
            $('#gap-upload-modal').hide();
            resetModals();
            loadFiles();
            showNotice(`Upload concluído: ${totalFiles} arquivo(s) enviado(s)`, 'success');
            return;
        }
        
        const batch = batches[currentBatch];
    if (window.gap_ajax && gap_ajax.debug) console.log(`📦 Processando lote ${currentBatch + 1}/${batches.length} (${batch.length} arquivos)`);
        
        const formData = new FormData();
        formData.append('action', 'gap_upload_files');
        formData.append('nonce', gap_ajax.nonce);
        formData.append('path', currentPath);
        
        batch.forEach((file, index) => {
            formData.append('files[]', file);
        });
        
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000, // 2 minutos timeout
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const batchProgress = (e.loaded / e.total) * 100;
                        const totalProgress = ((currentBatch * 100) + batchProgress) / batches.length;
                        $('.gap-progress-fill').css('width', totalProgress + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    if (window.gap_ajax && gap_ajax.debug) console.log('✅ Lote', currentBatch + 1, 'concluído:', response.data.uploaded, 'arquivos');
                    
                    // Mostrar erros parciais se houver
                    if (response.data.has_errors && response.data.errors) {
                        const errorList = response.data.errors.join('<br>');
                        showNotice('Lote ' + (currentBatch + 1) + ' - Alguns arquivos falharam:<br>' + errorList, 'warning');
                    }
                    
                    // Processar próximo lote
                    setTimeout(() => {
                        uploadBatches(batches, currentBatch + 1, totalFiles);
                    }, 500); // Pequena pausa entre lotes
                } else {
                    $('.gap-upload-progress').hide();
                    showError('Erro no lote ' + (currentBatch + 1) + ': ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('.gap-upload-progress').hide();
                if (window.gap_ajax && gap_ajax.debug) {
                    console.error('❌ Erro no lote', currentBatch + 1, ':', status, error);
                    console.error('Response:', xhr.responseText);
                }
                
                let errorMsg = 'Erro de conexão durante upload';
                if (status === 'timeout') {
                    errorMsg = 'Timeout - arquivo(s) muito grande(s) ou conexão lenta';
                } else if (xhr.status === 413) {
                    errorMsg = 'Arquivo(s) muito grande(s) para o servidor (413 Request Entity Too Large)';
                } else if (xhr.status === 500) {
                    errorMsg = 'Erro interno do servidor - verifique logs PHP';
                } else if (xhr.status === 502) {
                    errorMsg = 'Bad Gateway - servidor sobrecarregado';
                } else if (xhr.status === 504) {
                    errorMsg = 'Gateway Timeout - processo muito lento';
                }
                
                // Tentar mostrar uma mensagem mais específica baseada na resposta
                try {
                    if (xhr.responseText) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data) {
                            errorMsg += ': ' + response.data;
                        }
                    }
                } catch (e) {
                    // Ignora erro de parsing
                }
                
                showError(errorMsg + ` (Lote ${currentBatch + 1}/${batches.length})`);
                
                // Perguntar se quer continuar com próximo lote
                if (currentBatch + 1 < batches.length) {
                    setTimeout(() => {
                        if (confirm(`Lote ${currentBatch + 1} falhou. Continuar com próximo lote?`)) {
                            uploadBatches(batches, currentBatch + 1, totalFiles);
                        }
                    }, 1000);
                }
            }
        });
    }
    
    function createFolder() {
        const folderName = $('#gap-folder-name').val().trim();
        
        if (!folderName) {
            showError('Nome da pasta é obrigatório');
            return;
        }
        
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gap_create_folder',
                nonce: gap_ajax.nonce,
                path: currentPath,
                name: folderName
            },
            success: function(response) {
                if (response.success) {
                    $('#gap-folder-modal').hide();
                    resetModals();
                    loadFiles();
                    showNotice('Pasta criada com sucesso', 'success');
                } else {
                    showError('Erro ao criar pasta: ' + response.data);
                }
            },
            error: function() {
                showError('Erro de conexão');
            }
        });
    }
    
    function deleteItem(item) {
        const itemName = item.data('name');
        const itemType = item.data('type');
        const message = itemType === 'folder' 
            ? `Tem certeza que deseja excluir a pasta "${itemName}" e todo seu conteúdo?`
            : `Tem certeza que deseja excluir o arquivo "${itemName}"?`;
        
        if (!confirm(message)) return;
        
        const itemPath = currentPath ? currentPath + '/' + itemName : itemName;
        
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gap_delete_item',
                nonce: gap_ajax.nonce,
                path: itemPath
            },
            success: function(response) {
                if (response.success) {
                    loadFiles();
                    showNotice('Item excluído com sucesso', 'success');
                } else {
                    showError('Erro ao excluir: ' + response.data);
                }
            },
            error: function() {
                showError('Erro de conexão');
            }
        });
    }
    
    function downloadFile(item) {
        const fileName = item.data('name');
        const filePath = currentPath ? currentPath + '/' + fileName : fileName;
        
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gap_get_file_info',
                nonce: gap_ajax.nonce,
                path: filePath
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    const link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    showError('Erro ao obter informações do arquivo');
                }
            },
            error: function() {
                showError('Erro de conexão');
            }
        });
    }
    
    function resetModals() {
        $('#gap-file-input').val('');
        $('#gap-folder-name').val('');
        $('.gap-upload-progress').hide();
        $('.gap-progress-fill').css('width', '0%');
    }

    function updateBulkButtons() {
        if (selectedItems.size > 0) {
            $('#gap-bulk-delete, #gap-bulk-download').prop('disabled', false);
        } else {
            $('#gap-bulk-delete, #gap-bulk-download').prop('disabled', true);
        }
    }

    function bulkDelete() {
        if (selectedItems.size === 0) return;
        if (!confirm(`Excluir ${selectedItems.size} item(ns) selecionado(s)?`)) return;
        const names = Array.from(selectedItems);
        let processed = 0;
        let errors = [];

        function next() {
            if (processed >= names.length) {
                if (errors.length) {
                    showError('Alguns itens falharam: ' + errors.join('; '));
                } else {
                    showNotice('Itens excluídos com sucesso', 'success');
                }
                selectedItems.clear();
                loadFiles();
                return;
            }
            const name = names[processed];
            const itemPath = currentPath ? currentPath + '/' + name : name;
            $.ajax({
                url: gap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gap_delete_item',
                    nonce: gap_ajax.nonce,
                    path: itemPath
                },
                success: function(response) {
                    if (!response.success) {
                        errors.push(name + ': ' + response.data);
                    }
                    processed++;
                    next();
                },
                error: function() {
                    errors.push(name + ': erro de conexão');
                    processed++;
                    next();
                }
            });
        }
        next();
    }

    function bulkDownload() {
        if (selectedItems.size === 0) return;
        const names = Array.from(selectedItems);
        const paths = names.map(n => currentPath ? currentPath + '/' + n : n);

        // Tenta criar ZIP em lote pelo servidor
        $.ajax({
            url: gap_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gap_create_zip',
                nonce: gap_ajax.nonce,
                paths: paths
            },
            success: function(response) {
                if (response && response.success && response.data && response.data.url) {
                    if (window.gap_ajax && gap_ajax.debug) console.log('🧩 ZIP gerado:', response.data.name);
                    window.location = response.data.url;
                    showNotice('Iniciando download ZIP com ' + names.length + ' item(ns)', 'success');
                } else {
                    if (window.gap_ajax && gap_ajax.debug) console.warn('ZIP indisponível, usando fallback. Resposta:', response);
                    sequentialDownloads(names);
                }
            },
            error: function(xhr) {
                if (window.gap_ajax && gap_ajax.debug) console.error('Falha ao criar ZIP, fallback sequencial. Status:', xhr.status);
                sequentialDownloads(names);
            }
        });
    }

    function sequentialDownloads(names) {
        let index = 0;
        function next() {
            if (index >= names.length) {
                showNotice('Downloads iniciados para ' + names.length + ' arquivo(s)');
                return;
            }
            const name = names[index];
            const filePath = currentPath ? currentPath + '/' + name : name;
            $.ajax({
                url: gap_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'gap_get_file_info',
                    nonce: gap_ajax.nonce,
                    path: filePath
                },
                success: function(response) {
                    if (response.success && response.data.url) {
                        const link = document.createElement('a');
                        link.href = response.data.url;
                        link.download = name;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                    index++;
                    setTimeout(next, 150);
                },
                error: function() {
                    index++;
                    setTimeout(next, 150);
                }
            });
        }
        next();
    }
    
    function showNotice(message, type = 'info') {
        const noticeClass = type === 'success' ? 'notice-success' : type === 'error' ? 'notice-error' : 'notice-info';
        
        const notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dispensar este aviso.</span>
                </button>
            </div>
        `);
        
        $('.wrap h1').after(notice);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 5000);
        
        // Manual dismiss
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(() => notice.remove());
        });
    }
    
    function showError(message) {
        showNotice(message, 'error');
    }
    
    function updatePagination() {
        // Remove paginação existente
        $('.gap-pagination').remove();
        
        // Mostrar paginação a partir de 10 itens (>= 10)
        if (totalFiles < 10) {
            return; // Não exibe paginação se menos de 10
        }
        
        const totalPages = Math.ceil(totalFiles / itemsPerPage);
        let paginationHtml = '<div class="gap-pagination">';
        
        // Botão Anterior
        paginationHtml += `<button class="gap-pagination-btn ${currentPage === 1 ? 'disabled' : ''}" 
                          onclick="changeAdminPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
        
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
            paginationHtml += `<button class="gap-pagination-btn" onclick="changeAdminPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHtml += '<span class="gap-pagination-ellipsis">...</span>';
            }
        }
        
        // Páginas visíveis
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<button class="gap-pagination-btn ${i === currentPage ? 'active' : ''}" 
                              onclick="changeAdminPage(${i})">${i}</button>`;
        }
        
        // Última página se não estiver visível
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += '<span class="gap-pagination-ellipsis">...</span>';
            }
            paginationHtml += `<button class="gap-pagination-btn" onclick="changeAdminPage(${totalPages})">${totalPages}</button>`;
        }
        
        // Botão Próxima
        paginationHtml += `<button class="gap-pagination-btn ${currentPage === totalPages ? 'disabled' : ''}" 
                          onclick="changeAdminPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;
        
        // Info da página
        const startItem = (currentPage - 1) * itemsPerPage + 1;
        const endItem = Math.min(currentPage * itemsPerPage, totalFiles);
        paginationHtml += `<span class="gap-pagination-info">${startItem}-${endItem} de ${totalFiles}</span>`;
        
        // Items por página
        paginationHtml += `
            <div class="gap-items-per-page">
                Mostrar: 
                <select onchange="changeAdminItemsPerPage(this.value)">
                    <option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
                    <option value="20" ${itemsPerPage === 20 ? 'selected' : ''}>20</option>
                    <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100</option>
                </select>
            </div>
        `;
        
        paginationHtml += '</div>';
        
        // Adiciona a paginação após o grid
        $('#gap-file-grid').after(paginationHtml);
    }
    
    // Função global para mudança de página no admin
    window.changeAdminPage = function(page) {
        const totalPages = Math.ceil(totalFiles / itemsPerPage);
        if (page >= 1 && page <= totalPages && page !== currentPage) {
            currentPage = page;
            displayCurrentPage();
            updatePagination();
        }
    };
    
    // Função global para mudança de itens por página no admin
    window.changeAdminItemsPerPage = function(newItemsPerPage) {
        itemsPerPage = parseInt(newItemsPerPage);
        currentPage = 1;
        displayCurrentPage();
        updatePagination();
    };
});
