<?php
namespace Elementor;

if (!defined('ABSPATH')) {
    exit;
}

// Verifica se o Elementor está ativo antes de definir a classe
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class GAPElementorWidget extends Widget_Base {
    
    public function get_name() {
        return 'gap_file_explorer';
    }
    
    public function get_title() {
        return __('Explorador de Arquivos Pro', 'gerenciador-arquivos-pro');
    }
    
    public function get_icon() {
        return 'eicon-folder';
    }
    
    public function get_categories() {
        return ['general'];
    }
    
    public function get_keywords() {
        return ['arquivos', 'files', 'explorador', 'explorer', 'upload', 'download'];
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Configurações', 'gerenciador-arquivos-pro'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'base_folder',
            [
                'label' => __('Pasta Base', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'description' => __('Pasta dentro de gap-files (deixe vazio para raiz)', 'gerenciador-arquivos-pro'),
                'placeholder' => __('Ex: documentos', 'gerenciador-arquivos-pro'),
            ]
        );
        
        $this->add_control(
            'widget_title',
            [
                'label' => __('Título do Widget', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Explorador de Arquivos', 'gerenciador-arquivos-pro'),
            ]
        );
        
        $this->add_control(
            'show_title',
            [
                'label' => __('Mostrar Título', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'gerenciador-arquivos-pro'),
                'label_off' => __('Não', 'gerenciador-arquivos-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $base_folder = sanitize_text_field($settings['base_folder']);
        $widget_title = sanitize_text_field($settings['widget_title']);
        $show_title = $settings['show_title'] === 'yes';
        
        // Enqueue scripts and styles
        wp_enqueue_script('gap-frontend-js');
        wp_enqueue_style('gap-frontend-css');
        
        ?>
        <div class="gap-frontend-container" data-base-folder="<?php echo esc_attr($base_folder); ?>" 
             style="background:#fff; border:1px solid #e1e5e9; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif; overflow:hidden; max-width:700px; margin:0 auto;">
            
            <?php if ($show_title && $widget_title): ?>
            <div class="gap-frontend-header" 
                 style="background:linear-gradient(135deg, #104B3B 0%, #218865 100%); color:white !important; padding:20px; text-align:center;">
                <h3 style="margin:0; font-weight:600; font-size:24px; color:white !important;"><?php echo esc_html($widget_title); ?></h3>
            </div>
            <?php endif; ?>
            
            <div class="gap-frontend-nav" 
                 style="background:#f8f9fa; padding:15px 20px; border-bottom:1px solid #e1e5e9; display:flex; justify-content:space-between; align-items:center;">
                <div class="gap-path-display" 
                     style="display:flex; align-items:center; font-size:14px; color:#666;">
                    <span>📍 Caminho:</span>
                    <span class="gap-current-path" 
                          style="font-family:monospace; background:#e9ecef; padding:4px 8px; border-radius:4px; color:#495057; margin-left:8px;"><?php echo $base_folder ? '/' . esc_html($base_folder) : '/'; ?></span>
                </div>
                
                <button class="gap-back-btn" disabled 
                        style="background:#e9ecef; color:#6c757d; border:none; padding:8px 16px; border-radius:6px; cursor:not-allowed; font-size:14px; display:flex; align-items:center; gap:5px;">
                    ← Voltar
                </button>
            </div>
            
            <div class="gap-frontend-content" 
                 style="padding:15px; min-height:250px;">
                <!-- Content will be loaded via JavaScript -->
            </div>
        </div>
        <?php
    }
    
    protected function content_template() {
        // Replica exatamente o HTML do frontend com estilos inline aplicados
        ?>
        <#
        var base_folder = settings.base_folder ? settings.base_folder : '';
        var widget_title = settings.widget_title ? settings.widget_title : 'Explorador de Arquivos';
        var show_title = settings.show_title === 'yes';
        var display_path = base_folder ? '/' + base_folder : '/';
        #>
        <div class="gap-frontend-container" data-base-folder="{{ base_folder }}" 
             style="background:#fff; border:1px solid #e1e5e9; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif; overflow:hidden; max-width:700px; margin:0 auto;">
            
            <# if (show_title && widget_title) { #>
            <div class="gap-frontend-header" 
                 style="background:linear-gradient(135deg, #104B3B 0%, #218865 100%); color:white !important; padding:20px; text-align:center;">
                <h3 style="margin:0; font-weight:600; font-size:24px; color:white !important;">{{ widget_title }}</h3>
            </div>
            <# } #>
            
            <div class="gap-frontend-nav" 
                 style="background:#f8f9fa; padding:15px 20px; border-bottom:1px solid #e1e5e9; display:flex; justify-content:space-between; align-items:center;">
                <div class="gap-path-display" 
                     style="display:flex; align-items:center; font-size:14px; color:#666;">
                    <span>📍 Caminho:</span>
                    <span class="gap-current-path" 
                          style="font-family:monospace; background:#e9ecef; padding:4px 8px; border-radius:4px; color:#495057; margin-left:8px;">{{ display_path }}</span>
                </div>
                <button class="gap-back-btn" disabled 
                        style="background:#e9ecef; color:#6c757d; border:none; padding:8px 16px; border-radius:6px; cursor:not-allowed; font-size:14px; display:flex; align-items:center; gap:5px;">
                    ← Voltar
                </button>
            </div>
            
            <div class="gap-frontend-content" 
                 style="padding:15px; min-height:250px;">
                <div class="gap-file-grid" style="display:block;">
                    <div style="text-align:center; padding:40px 20px; color:#6c757d;">
                        <div style="font-size:3em; margin-bottom:15px; opacity:0.5;">📁</div>
                        <div style="font-size:1.2em; margin-bottom:8px;">
                            <# if (base_folder) { #>
                                Pasta: {{ base_folder }}
                            <# } else { #>
                                Explorador de Arquivos
                            <# } #>
                        </div>
                        <div style="font-size:1em; opacity:0.8;">Os arquivos aparecerão aqui no site publicado</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
