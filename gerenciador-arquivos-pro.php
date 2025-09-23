<?php
/*
Plugin Name: Gerenciador de Arquivos Pro
Description: Plugin completo para gerenciar arquivos com painel administrativo e widget Elementor
Version: 2.9.5
Author: Artur Guimarães de Freitas
*/

if (!defined('ABSPATH')) exit;

define('GAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Versão e modo de desenvolvimento
if (!defined('GAPR_VERSION')) {
    define('GAPR_VERSION', '2.9.5');
}
if (!defined('GAPR_DEV')) {
    define('GAPR_DEV', false); // Em produção mantenha false; em desenvolvimento pode usar true
}

class GerenciadorArquivosPro {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_enqueue_scripts']);
        
        // Elementor Widget - só registra se o Elementor estiver ativo
        add_action('plugins_loaded', [$this, 'check_elementor_and_register']);
        
        // Debug: adiciona aviso se Elementor não estiver ativo
        add_action('admin_notices', [$this, 'elementor_dependency_notice']);
        
        // AJAX Handlers
        $this->register_ajax_handlers();
        
        // Download handler
        add_action('init', [$this, 'handle_file_download']);
        add_action('init', [$this, 'handle_zip_download']);
    }
    
    public function check_elementor_and_register() {
        // Verifica se o Elementor está instalado e ativo
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }
        
        // Registra o hook do Elementor de forma mais simples
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget'], 10);
    }
    
    public function init() {
        $this->create_base_directory();
    }
    
    private function create_base_directory() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        
        if (!file_exists($base_dir)) {
            wp_mkdir_p($base_dir);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Gerenciador de Arquivos Pro',
            'Arquivos Pro',
            'manage_options',
            'gerenciador-arquivos-pro',
            [$this, 'admin_page'],
            'dashicons-portfolio',
            30
        );
    }
    
    public function admin_page() {
        // Get PHP limits
        $upload_max = wp_max_upload_size();
        $post_max = ini_get('post_max_size');
        $max_uploads = ini_get('max_file_uploads');
        $max_execution = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        
        ?>
        <div class="wrap">
            <h1>Gerenciador de Arquivos Pro</h1>
            
            <div class="notice notice-info">
                <p><strong>ℹ️ Limites do Servidor:</strong></p>
                <ul style="margin-left: 20px;">
                    <li><strong>Tamanho máximo por arquivo:</strong> <?php echo size_format($upload_max); ?></li>
                    <li><strong>Tamanho máximo do POST:</strong> <?php echo $post_max; ?></li>
                    <li><strong>Máximo de arquivos por upload:</strong> <?php echo $max_uploads; ?></li>
                    <li><strong>Tempo máximo de execução:</strong> <?php echo $max_execution; ?>s</li>
                    <li><strong>Limite de memória:</strong> <?php echo $memory_limit; ?></li>
                </ul>
                <p><em>💡 Para uploads grandes, o sistema divide automaticamente em lotes menores.</em></p>
            </div>
            
            <div id="gap-admin-container">
                <div class="gap-toolbar">
                    <div class="gap-path-display">
                        <span class="gap-path-label">Caminho:</span>
                        <span id="gap-current-path">/</span>
                    </div>
                    <div class="gap-actions">
                        <button id="gap-select-toggle" class="button" title="Selecionar/Cancelar Seleção de Todos">
                            <span class="dashicons dashicons-yes"></span> Selecionar Todos
                        </button>
                        <button id="gap-bulk-download" class="button" disabled title="Download Selecionados">
                            <span class="dashicons dashicons-download"></span> Download Selecionados
                        </button>
                        <button id="gap-bulk-delete" class="button" disabled title="Excluir Selecionados">
                            <span class="dashicons dashicons-trash"></span> Excluir Selecionados
                        </button>
                        <button id="gap-upload-btn" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span> Upload
                        </button>
                        <button id="gap-new-folder-btn" class="button">
                            <span class="dashicons dashicons-plus-alt"></span> Nova Pasta
                        </button>
                    </div>
                </div>
                
                <div class="gap-content">
                    <div class="gap-navigation">
                        <button id="gap-back-btn" class="button" style="display:none;">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Voltar
                        </button>
                    </div>
                    
                    <div id="gap-file-grid" class="gap-file-grid">
                        <!-- Files will be loaded here -->
                    </div>
                </div>
                
                <!-- Modals -->
                <div id="gap-upload-modal" class="gap-modal" style="display:none;">
                    <div class="gap-modal-content">
                        <span class="gap-close">&times;</span>
                        <h3>Upload de Arquivos</h3>
                        <div class="gap-upload-area">
                            <input type="file" id="gap-file-input" multiple>
                            <div class="gap-drop-zone">
                                <p>Arraste arquivos aqui ou clique para selecionar</p>
                                <small>Máximo: <?php echo size_format($upload_max); ?> por arquivo | <?php echo $max_uploads; ?> arquivos por lote</small>
                            </div>
                        </div>
                        <div class="gap-upload-progress" style="display:none;">
                            <div class="gap-progress-bar">
                                <div class="gap-progress-fill"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="gap-folder-modal" class="gap-modal" style="display:none;">
                    <div class="gap-modal-content">
                        <span class="gap-close">&times;</span>
                        <h3>Nova Pasta</h3>
                        <input type="text" id="gap-folder-name" placeholder="Nome da pasta">
                        <button id="gap-create-folder" class="button button-primary">Criar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Retorna versão de asset (filemtime em DEV, versão fixa em PROD)
    private function asset_version($relative_path) {
        $full = trailingslashit(dirname(__FILE__)) . ltrim($relative_path, '/');
        if (GAPR_DEV && file_exists($full)) {
            return filemtime($full);
        }
        return GAPR_VERSION;
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_gerenciador-arquivos-pro') return;
        
        wp_enqueue_script('gap-admin-js', GAP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], $this->asset_version('assets/js/admin.js'), true);
        wp_enqueue_style('gap-admin-css', GAP_PLUGIN_URL . 'assets/css/admin.css', [], $this->asset_version('assets/css/admin.css'));
        
        $max_upload = wp_max_upload_size();
        $post_max_raw = ini_get('post_max_size');
        $post_max_bytes = $this->parse_size_to_bytes($post_max_raw);
        $max_file_uploads = (int) ini_get('max_file_uploads');

        wp_localize_script('gap-admin-js', 'gap_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gap_nonce'),
            'max_upload_size' => $max_upload,
            'max_upload_size_human' => size_format($max_upload),
            'post_max_size' => $post_max_bytes,
            'post_max_size_human' => size_format($post_max_bytes),
            'max_file_uploads' => $max_file_uploads,
            'debug' => GAPR_DEV
        ]);
    }
    
    public function frontend_enqueue_scripts() {
        wp_register_script('gap-frontend-js', GAP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], $this->asset_version('assets/js/frontend.js'), true);
        wp_register_style('gap-frontend-css', GAP_PLUGIN_URL . 'assets/css/frontend.css', [], $this->asset_version('assets/css/frontend.css'));
        
        $max_upload = wp_max_upload_size();
        $post_max_raw = ini_get('post_max_size');
        $post_max_bytes = $this->parse_size_to_bytes($post_max_raw);
        $max_file_uploads = (int) ini_get('max_file_uploads');

        wp_localize_script('gap-frontend-js', 'gap_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gap_nonce'),
            'max_upload_size' => $max_upload,
            'max_upload_size_human' => size_format($max_upload),
            'post_max_size' => $post_max_bytes,
            'post_max_size_human' => size_format($post_max_bytes),
            'max_file_uploads' => $max_file_uploads,
            'debug' => GAPR_DEV
        ]);
    }
    
    public function register_elementor_widget($widgets_manager = null) {
        // Verifica se a classe base do Elementor existe
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }
        
        // Inclui o arquivo do widget
        require_once GAP_PLUGIN_DIR . 'includes/elementor-widget.php';
        
        // Se não foi passado o widgets_manager como parâmetro, pega da instância do Elementor
        if (!$widgets_manager && class_exists('\Elementor\Plugin')) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        }
        
        // Registra o widget de forma simples
        if ($widgets_manager && method_exists($widgets_manager, 'register')) {
            $widgets_manager->register(new \Elementor\GAPElementorWidget());
        }
    }
    
    private function register_ajax_handlers() {
        // Admin and public AJAX handlers
        $handlers = ['list_files', 'upload_files', 'create_folder', 'delete_item', 'get_file_info', 'create_zip'];
        
        foreach ($handlers as $handler) {
            add_action('wp_ajax_gap_' . $handler, [$this, 'ajax_' . $handler]);
            add_action('wp_ajax_nopriv_gap_' . $handler, [$this, 'ajax_' . $handler]);
        }
    }

    public function ajax_create_zip() {
        check_ajax_referer('gap_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error('Permissões insuficientes');
        }

        if (!class_exists('ZipArchive')) {
            wp_send_json_error('ZIP não suportado no servidor (ZipArchive ausente)');
        }

        $paths = isset($_POST['paths']) ? (array) $_POST['paths'] : [];
        $paths = array_map(function($p){ return trim(sanitize_text_field($p), '/'); }, $paths);
        $paths = array_values(array_filter($paths, function($p){ return $p !== ''; }));

        if (empty($paths)) {
            wp_send_json_error('Nenhum item selecionado para compactar');
        }

        // Base dirs
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $tmp_dir = $upload_dir['basedir'] . '/.gap_tmp_zips'; // Pasta oculta fora do diretório visível

        if (!file_exists($tmp_dir)) {
            wp_mkdir_p($tmp_dir);
        }

        // Simple cleanup: remove old zips (>12h)
        $this->cleanup_old_zips($tmp_dir, 60 * 60 * 12);

        // Create unique zip filename
        $token = wp_generate_password(12, false, false);
        $zip_name = 'gap-download-' . date('Ymd-His') . '-' . $token . '.zip';
        $zip_path = $tmp_dir . '/' . $zip_name;

        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            wp_send_json_error('Falha ao criar arquivo ZIP');
        }

        $added = 0;
        foreach ($paths as $relPath) {
            $fullPath = $base_dir . '/' . $relPath;

            $real_full = realpath($fullPath);
            $real_base = realpath($base_dir);
            if (!$real_full || !$real_base || strpos($real_full, $real_base) !== 0) {
                continue; // skip invalid
            }

            if (is_dir($real_full)) {
                $this->zip_add_directory($zip, $real_full, $base_dir);
                $added++;
            } elseif (is_file($real_full)) {
                // Usar apenas o nome do arquivo, sem estrutura de pastas
                $localname = basename($real_full);
                $zip->addFile($real_full, $localname);
                $added++;
            }
        }

        $zip->close();

        if ($added === 0 || !file_exists($zip_path)) {
            @unlink($zip_path);
            wp_send_json_error('Nenhum arquivo válido para compactar');
        }

        $download_url = add_query_arg([
            'gap_zip' => 1,
            'file' => rawurlencode($zip_name),
            'nonce' => wp_create_nonce('gap_nonce')
        ], home_url('/'));

        wp_send_json_success([
            'url' => $download_url,
            'name' => $zip_name
        ]);
    }
    
    public function ajax_list_files() {
        check_ajax_referer('gap_nonce', 'nonce');
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $base_url = $upload_dir['baseurl'] . '/gap-files';
        
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        
        // Remove barras extras e limpa o caminho
        $path = trim($path, '/');
        
        $current_dir = $base_dir;
        $current_url = $base_url;
        
        // Se há um caminho especificado, adiciona-o
        if (!empty($path)) {
            $current_dir .= '/' . $path;
            // Codifica cada parte do caminho separadamente para URLs corretas em pastas aninhadas
            $path_parts = explode('/', $path);
            $encoded_parts = array_map('rawurlencode', $path_parts);
            $current_url .= '/' . implode('/', $encoded_parts);
        }
        
        // Cria o diretório base se não existir
        if (!file_exists($base_dir)) {
            wp_mkdir_p($base_dir);
        }
        
        // Cria o diretório atual se não existir
        if (!file_exists($current_dir)) {
            wp_mkdir_p($current_dir);
        }
        
        // Security check - verifica se o caminho está dentro do diretório base
        $real_current = realpath($current_dir);
        $real_base = realpath($base_dir);
        
        if (!$real_current || !$real_base || strpos($real_current, $real_base) !== 0) {
            wp_send_json_error('Caminho inválido: ' . $path);
        }
        
        if (!is_dir($current_dir)) {
            wp_send_json_error('Diretório não encontrado: ' . $current_dir);
        }
        
        $items = [];
        $files = scandir($current_dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $file_path = $current_dir . '/' . $file;
            $file_url = $current_url . '/' . rawurlencode($file);
            
            $item = [
                'name' => $file,
                'type' => is_dir($file_path) ? 'folder' : 'file',
                'size' => is_file($file_path) ? size_format(filesize($file_path)) : '',
                'modified' => date('d/m/Y H:i', filemtime($file_path)),
                'url' => is_file($file_path) ? $file_url : ''
            ];
            
            if (is_file($file_path)) {
                $item['extension'] = pathinfo($file, PATHINFO_EXTENSION);
                $item['is_image'] = in_array(strtolower($item['extension']), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            }
            
            $items[] = $item;
        }
        
        // Sort folders first, then files
        usort($items, function($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['type'] === 'folder' ? -1 : 1;
        });
        
        wp_send_json_success([
            'items' => $items,
            'current_path' => $path
        ]);
    }
    
    public function ajax_upload_files() {
        check_ajax_referer('gap_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permissões insuficientes');
        }
        
        // Aumentar tempo limite temporariamente
        set_time_limit(300); // 5 minutos
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        $target_dir = $base_dir . '/' . ltrim($path, '/');
        
        // Criar diretório se não existir
        if (!wp_mkdir_p($target_dir)) {
            wp_send_json_error('Não foi possível criar o diretório de destino');
        }
        
        // Security check
        if (strpos(realpath($target_dir), realpath($base_dir)) !== 0) {
            wp_send_json_error('Caminho inválido');
        }
        
        $uploaded_files = [];
        $errors = [];
        $total_size = 0;
        
        if (!empty($_FILES['files'])) {
            // Verificar limites PHP
            $max_uploads = ini_get('max_file_uploads') ?: 20;
            $max_size = wp_max_upload_size();
            
            foreach ($_FILES['files']['name'] as $key => $name) {
                // Verificar erros de upload
                $error = $_FILES['files']['error'][$key];
                $size = $_FILES['files']['size'][$key];
                $tmp_name = $_FILES['files']['tmp_name'][$key];
                
                if ($error !== UPLOAD_ERR_OK) {
                    $error_messages = [
                        UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do PHP)',
                        UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
                        UPLOAD_ERR_PARTIAL => 'Upload parcial - tente novamente',
                        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
                        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
                        UPLOAD_ERR_CANT_WRITE => 'Erro de escrita no disco',
                        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
                    ];
                    
                    $error_msg = isset($error_messages[$error]) ? $error_messages[$error] : 'Erro desconhecido (' . $error . ')';
                    $errors[] = $name . ': ' . $error_msg;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('GAP Upload Error - ' . $name . ': ' . $error_msg);
                    }
                    continue;
                }
                
                // Verificar tamanho do arquivo
                if ($size > $max_size) {
                    $errors[] = $name . ': Muito grande (' . size_format($size) . ' > ' . size_format($max_size) . ')';
                    continue;
                }
                
                $total_size += $size;
                
                // Verificar espaço em disco
                $free_space = disk_free_space($target_dir);
                if ($free_space !== false && $total_size > $free_space) {
                    $errors[] = 'Espaço insuficiente em disco';
                    break;
                }
                
                // Sanitizar nome do arquivo
                $filename = sanitize_file_name($name);
                
                // Verificar se arquivo já existe e criar nome único se necessário
                $target_file = $target_dir . '/' . $filename;
                $counter = 1;
                $file_info = pathinfo($filename);
                $base_name = $file_info['filename'];
                $extension = isset($file_info['extension']) ? '.' . $file_info['extension'] : '';
                
                while (file_exists($target_file)) {
                    $new_filename = $base_name . '_' . $counter . $extension;
                    $target_file = $target_dir . '/' . $new_filename;
                    $counter++;
                }
                
                // Mover arquivo
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_files[] = basename($target_file);
                } else {
                    $errors[] = $name . ': Falha ao mover arquivo';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('GAP Upload Failed to move: ' . $name . ' to ' . $target_file);
                    }
                }
            }
        } else {
            wp_send_json_error('Nenhum arquivo recebido');
        }
        
        $response = [
            'uploaded' => count($uploaded_files),
            'files' => $uploaded_files,
            'total_size' => size_format($total_size)
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['has_errors'] = true;
        }
        
        wp_send_json_success($response);
    }
    
    public function ajax_create_folder() {
        check_ajax_referer('gap_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permissões insuficientes');
        }
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        $folder_name = isset($_POST['name']) ? sanitize_file_name($_POST['name']) : '';
        
        if (empty($folder_name)) {
            wp_send_json_error('Nome da pasta é obrigatório');
        }
        
        $target_dir = $base_dir . '/' . ltrim($path, '/');
        $new_folder = $target_dir . '/' . $folder_name;
        
        // Security check
        if (strpos(realpath($target_dir), realpath($base_dir)) !== 0) {
            wp_send_json_error('Caminho inválido');
        }
        
        if (file_exists($new_folder)) {
            wp_send_json_error('Pasta já existe');
        }
        
        if (wp_mkdir_p($new_folder)) {
            wp_send_json_success('Pasta criada com sucesso');
        } else {
            wp_send_json_error('Erro ao criar pasta');
        }
    }
    
    public function ajax_delete_item() {
        check_ajax_referer('gap_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error('Permissões insuficientes');
        }
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $item_path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        $target = $base_dir . '/' . ltrim($item_path, '/');
        
        // Security check
        if (strpos(realpath(dirname($target)), realpath($base_dir)) !== 0) {
            wp_send_json_error('Caminho inválido');
        }
        
        if (!file_exists($target)) {
            wp_send_json_error('Item não encontrado');
        }
        
        if (is_dir($target)) {
            if ($this->delete_directory($target)) {
                wp_send_json_success('Pasta excluída com sucesso');
            } else {
                wp_send_json_error('Erro ao excluir pasta');
            }
        } else {
            if (unlink($target)) {
                wp_send_json_success('Arquivo excluído com sucesso');
            } else {
                wp_send_json_error('Erro ao excluir arquivo');
            }
        }
    }
    
    public function ajax_get_file_info() {
        check_ajax_referer('gap_nonce', 'nonce');
        
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $base_url = $upload_dir['baseurl'] . '/gap-files';
        $file_path = isset($_POST['path']) ? sanitize_text_field($_POST['path']) : '';
        
        // Remove barras extras e limpa o caminho
        $file_path = trim($file_path, '/');
        
        $target = $base_dir . '/' . $file_path;
        
        // Cria URL com encoding correto
        $path_parts = explode('/', $file_path);
        $encoded_parts = array_map('rawurlencode', $path_parts);
        $file_url = $base_url . '/' . implode('/', $encoded_parts);
        
        // Security check
        $real_target = realpath($target);
        $real_base = realpath($base_dir);
        
        if (!$real_target || !$real_base || strpos($real_target, $real_base) !== 0 || !file_exists($target)) {
            wp_send_json_error('Arquivo não encontrado: ' . $file_path);
        }
        
        // Verifica se é realmente um arquivo (não diretório)
        if (!is_file($target)) {
            wp_send_json_error('Caminho especificado não é um arquivo válido');
        }
        
        $info = [
            'name' => basename($target),
            'size' => size_format(filesize($target)),
            'modified' => date('d/m/Y H:i:s', filemtime($target)),
            'type' => wp_check_filetype($target)['type'] ?: 'application/octet-stream',
            'url' => $file_url,
            'path' => $file_path
        ];
        
        wp_send_json_success($info);
    }
    
    private function delete_directory($dir) {
        if (!is_dir($dir)) return false;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    public function handle_file_download() {
        if (!isset($_GET['gap_download']) || !isset($_GET['file']) || !isset($_GET['nonce'])) {
            return;
        }
        
        // Verifica nonce
        if (!wp_verify_nonce($_GET['nonce'], 'gap_nonce')) {
            wp_die('Acesso negado - nonce inválido');
        }
        
        $file_path = sanitize_text_field($_GET['file']);
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        
        // Remove barras extras
        $file_path = trim($file_path, '/');
        $target = $base_dir . '/' . $file_path;
        
        // Security checks
        $real_target = realpath($target);
        $real_base = realpath($base_dir);
        
        if (!$real_target || !$real_base || strpos($real_target, $real_base) !== 0) {
            wp_die('Acesso negado - caminho inválido');
        }
        
        if (!file_exists($target) || !is_file($target)) {
            wp_die('Arquivo não encontrado: ' . esc_html(basename($target)));
        }
        
        // Headers para download
        $filename = basename($target);
        $file_size = filesize($target);
        $mime_type = wp_check_filetype($target)['type'] ?: 'application/octet-stream';
        
        // Limpa qualquer output anterior
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Define headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $file_size);
        
        // Desabilita compressão
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }
        
        // Envia o arquivo
        readfile($target);
        exit;
    }

    public function handle_zip_download() {
        if (!isset($_GET['gap_zip']) || !isset($_GET['file']) || !isset($_GET['nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_GET['nonce'], 'gap_nonce')) {
            wp_die('Acesso negado - nonce inválido');
        }

        $zip_name = basename(sanitize_text_field(wp_unslash($_GET['file'])));

        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/gap-files';
        $tmp_dir = $upload_dir['basedir'] . '/.gap_tmp_zips'; // Pasta oculta fora do diretório visível
        $zip_path = $tmp_dir . '/' . $zip_name;

        $real_zip = realpath($zip_path);
        $real_tmp = realpath($tmp_dir);
        if (!$real_zip || !$real_tmp || strpos($real_zip, $real_tmp) !== 0 || !file_exists($zip_path)) {
            wp_die('Arquivo ZIP não encontrado');
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zip_path));

        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }

        readfile($zip_path);
        @unlink($zip_path);
        exit;
    }

    private function zip_add_directory($zip, $dirPath, $base_dir) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($files as $file) {
            $real_path = $file->getPathname();
            if ($file->isFile()) {
                // Usar apenas o nome do arquivo, sem estrutura de pastas
                $localname = basename($real_path);
                $zip->addFile($real_path, $localname);
            }
            // Pular diretórios - não criar estrutura de pastas
        }
    }

    private function cleanup_old_zips($dir, $max_age_seconds) {
        if (!is_dir($dir)) return;
        $now = time();
        foreach (glob($dir . '/*.zip') as $file) {
            $mtime = @filemtime($file);
            if ($mtime && ($now - $mtime) > $max_age_seconds) {
                @unlink($file);
            }
        }
    }
    
    public function elementor_dependency_notice() {
        $elementor_active = false;
        if (function_exists('is_plugin_active')) {
            $elementor_active = is_plugin_active('elementor/elementor.php');
        } else if (class_exists('Elementor\\Plugin')) {
            $elementor_active = true;
        }
        if (!$elementor_active && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-info">
                <p><strong>Gerenciador de Arquivos Pro:</strong> Para usar o widget no frontend, instale e ative o plugin <strong>Elementor</strong>. O painel administrativo funciona normalmente.</p>
            </div>
            <?php
        }
    }

    private function parse_size_to_bytes($size) {
        if (is_numeric($size)) {
            return (int) $size;
        }
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        switch($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }
}

new GerenciadorArquivosPro();
