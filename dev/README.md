# Gerenciador de Arquivos Pro

Um plugin WordPress completo para gerenciamento de arquivos com interface administrativa moderna e widget Elementor para frontend.

## Características

### 🚀 Funcionalidades Principais
- **Painel Administrativo**: Interface moderna e intuitiva para gerenciar arquivos
- **Widget Elementor**: Exibição de arquivos no frontend através do Elementor
- **Upload de Arquivos**: Suporte a múltiplos arquivos com drag & drop
- **Gerenciamento de Pastas**: Criar, navegar e excluir pastas
- **Visualização de Imagens**: Preview automático de imagens
- **Download Seguro**: Links de download protegidos

### 🎨 Design Moderno
- Interface responsiva e mobile-friendly
- Animações suaves e transições
- Ícones intuitivos para diferentes tipos de arquivo
- Sistema de grid adaptativo
- Suporte a modo escuro

### 🔒 Segurança
- Verificação de nonce em todas as requisições AJAX
- Validação de caminhos para prevenir directory traversal
- Controle de permissões baseado em capabilities do WordPress
- Sanitização completa de inputs

## Instalação

1. Faça upload da pasta `gerenciador-arquivos-pro` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse o menu "Arquivos Pro" no painel administrativo

## Como Usar

### Painel Administrativo
1. Acesse **Arquivos Pro** no menu do WordPress
2. Use os botões "Upload" e "Nova Pasta" para adicionar conteúdo
3. Clique em pastas para navegar
4. Use o botão "Voltar" ou os ícones de ação para gerenciar arquivos

### Widget Elementor
1. Edite uma página com Elementor
2. Procure por "Explorador de Arquivos Pro" na categoria "Geral" dos widgets
3. Você também pode pesquisar por: "arquivos", "files", "explorador" ou "explorer"
4. Arraste o widget para sua página
5. Configure a pasta base e personalização no painel de configurações

**Observação**: Se o widget não aparecer, verifique se:
- O plugin está ativo
- O Elementor está instalado e ativo
- Tente recarregar a página do editor do Elementor

## Estrutura de Arquivos

```
gerenciador-arquivos-pro/
├── gerenciador-arquivos-pro.php    # Arquivo principal do plugin
├── assets/
│   ├── css/
│   │   ├── admin.css               # Estilos do painel administrativo
│   │   └── frontend.css            # Estilos do widget frontend
│   └── js/
│       ├── admin.js                # JavaScript do painel admin
│       └── frontend.js             # JavaScript do widget
├── includes/
│   └── elementor-widget.php        # Widget do Elementor
└── README.md                       # Esta documentação
```

## Requisitos

- WordPress 5.0+
- PHP 7.4+
- Elementor (para widget frontend)

## Personalização

### CSS Customizado
Você pode sobrescrever os estilos adicionando CSS customizado no seu tema:

```css
/* Personalizar cores do container */
.gap-frontend-container {
    border: 2px solid #your-color;
    border-radius: 12px;
}

/* Personalizar cor de hover dos itens */
.gap-file-item:hover {
    border-color: #your-hover-color;
}
```

### Hooks Disponíveis
O plugin fornece alguns hooks para desenvolvedores:

```php
// Filtrar tipos de arquivo permitidos
add_filter('gap_allowed_file_types', function($types) {
    return array_merge($types, ['svg', 'webp']);
});

// Personalizar diretório base
add_filter('gap_base_directory', function($dir) {
    return wp_upload_dir()['basedir'] . '/custom-folder';
});
```

## Suporte

Para suporte e relatórios de bugs, entre em contato através do seu canal preferencial.

## Licença

Este plugin é licenciado sob GPL v2 ou posterior.

## Changelog

### v2.0
- Unificação dos plugins anteriores
- Interface administrativa completamente nova
- Widget Elementor aprimorado
- Melhorias de segurança e performance
- Design responsivo e moderno

### v1.0
- Versão inicial separada dos plugins
