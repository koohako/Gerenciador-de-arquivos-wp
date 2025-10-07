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

        $this->add_control(
            'title_area_height',
            [
                'label' => __('Altura da Área do Título', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 60,
                        'max' => 120,
                        'step' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 80,
                ],
                'description' => __('Altura total da área do cabeçalho', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_path',
            [
                'label' => __('Mostrar Caminho da Pasta', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'gerenciador-arquivos-pro'),
                'label_off' => __('Não', 'gerenciador-arquivos-pro'),
                'return_value' => 'yes',
                'default' => '',
                'description' => __('Mostra/oculta a barra de navegação com o caminho atual', 'gerenciador-arquivos-pro'),
            ]
        );

        $this->add_control(
            'show_back_button',
            [
                'label' => __('Botão Voltar no Cabeçalho', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'gerenciador-arquivos-pro'),
                'label_off' => __('Não', 'gerenciador-arquivos-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Mostra botão Voltar no cabeçalho quando caminho estiver oculto (só aparece dentro de pastas)', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'show_path!' => 'yes',
                    'show_title' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'height_mode',
            [
                'label' => __('Modo de Altura', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SELECT,
                'default' => 'fixed_px',
                'options' => [
                    'auto' => __('Automática (adapta ao conteúdo)', 'gerenciador-arquivos-pro'),
                    'fixed_px' => __('Altura Fixa (Pixels)', 'gerenciador-arquivos-pro'),
                    'fixed_lines' => __('Altura por Linhas', 'gerenciador-arquivos-pro'),
                ],
                'description' => __('Escolha como definir a altura da lista', 'gerenciador-arquivos-pro'),
            ]
        );

        $this->add_control(
            'content_height',
            [
                'label' => __('Altura da Lista (Pixels)', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 200,
                        'max' => 800,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'description' => __('Altura fixa em pixels da área de listagem de arquivos', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'height_mode' => 'fixed_px',
                ],
            ]
        );

        $this->add_control(
            'content_lines',
            [
                'label' => __('Número de Linhas', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 3,
                        'max' => 20,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'description' => __('Quantas linhas de arquivos serão exibidas exatamente', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'height_mode' => 'fixed_lines',
                ],
            ]
        );
        
        $this->add_control(
            'enable_scrollbar',
            [
                'label' => __('Ativar Barra de Rolagem', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Sim', 'gerenciador-arquivos-pro'),
                'label_off' => __('Não', 'gerenciador-arquivos-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Mostra barra de rolagem quando o conteúdo excede a altura definida', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'height_mode' => ['fixed_px', 'fixed_lines'],
                ],
            ]
        );

        $this->add_control(
            'max_height_auto',
            [
                'label' => __('Altura Máxima (Modo Auto)', 'gerenciador-arquivos-pro'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 300,
                        'max' => 1000,
                        'step' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 600,
                ],
                'description' => __('Altura máxima no modo automático (adiciona scroll se ultrapassar)', 'gerenciador-arquivos-pro'),
                'condition' => [
                    'height_mode' => 'auto',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $base_folder = sanitize_text_field($settings['base_folder']);
        $widget_title = sanitize_text_field($settings['widget_title']);
        $show_title = $settings['show_title'] === 'yes';
        $title_area_height = isset($settings['title_area_height']['size']) ? (int) $settings['title_area_height']['size'] : 80;
        $show_path = $settings['show_path'] === 'yes';
        $show_back_button = $settings['show_back_button'] === 'yes';
        $height_mode = isset($settings['height_mode']) ? $settings['height_mode'] : 'fixed_px';
        $content_height = isset($settings['content_height']['size']) ? (int) $settings['content_height']['size'] : 400;
        $content_lines = isset($settings['content_lines']['size']) ? (int) $settings['content_lines']['size'] : 8;
        $enable_scrollbar = $settings['enable_scrollbar'] === 'yes';
        $max_height_auto = isset($settings['max_height_auto']['size']) ? (int) $settings['max_height_auto']['size'] : 600;
        
        // Enqueue scripts and styles
        wp_enqueue_script('gap-frontend-js');
        wp_enqueue_style('gap-frontend-css');
        
        // Build content styles based on height mode
        $content_styles = 'padding:15px;';
        
        if ($height_mode === 'fixed_px') {
            // Modo altura fixa em pixels
            $content_styles .= 'height:' . $content_height . 'px;';
            if ($enable_scrollbar) {
                $content_styles .= 'overflow-y:auto;';
            } else {
                $content_styles .= 'overflow:hidden;';
            }
        } elseif ($height_mode === 'fixed_lines') {
            // Modo altura por linhas (cada linha tem exatamente 60px + 3px de margin)
            $line_height = 63; // Altura exata: 60px do item + 3px margin-bottom
            $calculated_height = $content_lines * $line_height;
            $content_styles .= 'height:' . $calculated_height . 'px;';
            if ($enable_scrollbar) {
                $content_styles .= 'overflow-y:auto;';
            } else {
                $content_styles .= 'overflow:hidden;';
            }
        } else {
            // Modo automático
            $content_styles .= 'min-height:250px;';
            $content_styles .= 'max-height:' . $max_height_auto . 'px;';
            $content_styles .= 'overflow-y:auto;';
        }
        
        ?>
        <div class="gap-frontend-container" data-base-folder="<?php echo esc_attr($base_folder); ?>" 
             style="background:#fff; border:1px solid #e1e5e9; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif; overflow:hidden; max-width:700px; margin:0 auto;">
            
            <?php if ($show_title && $widget_title): ?>
            <div class="gap-frontend-header" 
                 style="background:linear-gradient(135deg, #104B3B 0%, #218865 100%); color:white !important; height:<?php echo $title_area_height; ?>px; text-align:center; position:relative; display:flex; align-items:center; justify-content:center;">
                <h3 style="margin:0; font-weight:600; font-size:24px; color:white !important;"><?php echo esc_html($widget_title); ?></h3>
                <?php if (!$show_path && $show_back_button): ?>
                <button class="gap-header-back-btn" style="display:none !important; position:absolute; right:20px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.2); color:white; border:1px solid rgba(255,255,255,0.3); padding:8px 12px; border-radius:6px; font-size:14px; align-items:center; gap:5px; transition:all 0.3s ease;">
                    ← Voltar
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_path): ?>
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
            <?php endif; ?>
            
            <div class="gap-frontend-content" 
                 style="<?php echo esc_attr($content_styles); ?>">
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
        var title_area_height = settings.title_area_height && settings.title_area_height.size ? settings.title_area_height.size : 80;
        var show_path = settings.show_path === 'yes';
        var show_back_button = settings.show_back_button === 'yes';
        var display_path = base_folder ? '/' + base_folder : '/';
        var height_mode = settings.height_mode ? settings.height_mode : 'fixed_px';
        var content_height = settings.content_height && settings.content_height.size ? settings.content_height.size : 400;
        var content_lines = settings.content_lines && settings.content_lines.size ? settings.content_lines.size : 8;
        var enable_scrollbar = settings.enable_scrollbar === 'yes';
        var max_height_auto = settings.max_height_auto && settings.max_height_auto.size ? settings.max_height_auto.size : 600;
        
        var content_styles = 'padding:15px;';
        var height_info = '';
        
        if (height_mode === 'fixed_px') {
            content_styles += 'height:' + content_height + 'px;';
            if (enable_scrollbar) {
                content_styles += 'overflow-y:auto;';
            } else {
                content_styles += 'overflow:hidden;';
            }
            height_info = 'Altura: ' + content_height + 'px (Fixa) | Scroll: ' + (enable_scrollbar ? 'Ativado' : 'Desativado');
        } else if (height_mode === 'fixed_lines') {
            var line_height = 63; // Altura exata: 60px do item + 3px margin-bottom
            var calculated_height = content_lines * line_height;
            content_styles += 'height:' + calculated_height + 'px;';
            if (enable_scrollbar) {
                content_styles += 'overflow-y:auto;';
            } else {
                content_styles += 'overflow:hidden;';
            }
            height_info = 'Altura: ' + content_lines + ' linhas (' + calculated_height + 'px) | Scroll: ' + (enable_scrollbar ? 'Ativado' : 'Desativado');
        } else {
            content_styles += 'min-height:250px;';
            content_styles += 'max-height:' + max_height_auto + 'px;';
            content_styles += 'overflow-y:auto;';
            height_info = 'Altura: Automática (max: ' + max_height_auto + 'px)';
        }
        #>
        <div class="gap-frontend-container" data-base-folder="{{ base_folder }}" 
             style="background:#fff; border:1px solid #e1e5e9; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,Cantarell,sans-serif; overflow:hidden; max-width:700px; margin:0 auto;">
            
            <# if (show_title && widget_title) { #>
            <div class="gap-frontend-header" 
                 style="background:linear-gradient(135deg, #104B3B 0%, #218865 100%); color:white !important; height:{{ title_area_height }}px; text-align:center; position:relative; display:flex; align-items:center; justify-content:center;">
                <h3 style="margin:0; font-weight:600; font-size:24px; color:white !important;">{{ widget_title }}</h3>
                <# if (!show_path && show_back_button) { #>
                <button class="gap-header-back-btn" 
                        style="display:none; position:absolute; right:20px; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.2); color:white; border:1px solid rgba(255,255,255,0.3); padding:8px 12px; border-radius:6px; font-size:14px; align-items:center; gap:5px; transition:all 0.3s ease;">
                    ← Voltar (Preview)
                </button>
                <# } #>
            </div>
            <# } #>
            
            <# if (show_path) { #>
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
            <# } #>
            
            <div class="gap-frontend-content" 
                 style="{{ content_styles }}">
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
                        <div style="font-size:0.9em; opacity:0.6; margin-top:10px;">
                            {{ height_info }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
