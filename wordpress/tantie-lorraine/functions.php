<?php
/**
 * Tema Tantie Lorraine's: estilos, ajustes de privacidade e configuração inicial do site.
 *
 * @package tantie-lorraine
 */

defined( 'ABSPATH' ) || exit;

/**
 * Páginas criadas na primeira ativação: slug => [título, padrão com o conteúdo, descrição para motores de pesquisa].
 * O conteúdo de cada página é um bloco "padrão" do tema; ao abrir a página no editor, passa a texto editável.
 */
function tl_paginas() {
	return array(
		'inicio'      => array( 'Início', 'pagina-inicio', 'Compotas de malagueta com sabores de Trindade e Tobago, feitas à mão em pequenos lotes. Brevemente em Portugal.' ),
		'historia'    => array( 'História', 'pagina-historia', 'A história de Lorraine e da Tantie Lorraine\'s, de Trindade e Tobago para Surrey.' ),
		'produtos'    => array( 'Produtos', 'pagina-produtos', 'Compotas de malagueta da Tantie Lorraine\'s: ingredientes, alergénios e informação nutricional. Brevemente em Portugal.' ),
		'eventos'     => array( 'Eventos', 'pagina-eventos', 'Aulas de cozinha das Caraíbas com a Tantie Lorraine.' ),
		'contactos'   => array( 'Contactos', 'pagina-contactos', 'Contactos da Tantie Lorraine\'s em Portugal.' ),
		'privacidade' => array( 'Privacidade e informação legal', 'pagina-privacidade', 'Política de privacidade e informação legal do site tantielorraine.pt.' ),
	);
}

/** Endereço de uma página do site pelo slug (com reserva para o caso de a página ainda não existir). */
function tl_url( $slug ) {
	$pagina = get_page_by_path( $slug );
	if ( 'inicio' === $slug || ! $pagina ) {
		return 'inicio' === $slug ? home_url( '/' ) : home_url( '/' . $slug . '/' );
	}
	return get_permalink( $pagina );
}

/** Endereço de um ficheiro de imagem do tema. */
function tl_img( $ficheiro ) {
	return esc_url( get_theme_file_uri( 'assets/img/' . $ficheiro ) );
}

/** Configuração inicial: corre uma única vez, na primeira ativação do tema. */
function tl_configurar_site() {
	if ( get_option( 'tl_configurado' ) ) {
		return;
	}
	$ids = array();
	foreach ( tl_paginas() as $slug => $dados ) {
		$existente = get_page_by_path( $slug );
		if ( $existente ) {
			$ids[ $slug ] = $existente->ID;
			continue;
		}
		$ids[ $slug ] = wp_insert_post(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_title'     => $dados[0],
				'post_name'      => $slug,
				'post_excerpt'   => $dados[2],
				'post_content'   => '<!-- wp:pattern {"slug":"tantie-lorraine/' . $dados[1] . '"} /-->',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			)
		);
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['inicio'] );
	update_option( 'wp_page_for_privacy_policy', $ids['privacidade'] );
	update_option( 'blogname', "Tantie Lorraine's" );
	update_option( 'blogdescription', 'Compotas de malagueta de Trindade e Tobago' );
	update_option( 'default_comment_status', 'closed' );
	update_option( 'default_ping_status', 'closed' );
	update_option( 'permalink_structure', '/%postname%/' );
	flush_rewrite_rules();
	update_option( 'tl_configurado', 1 );
}
add_action( 'after_switch_theme', 'tl_configurar_site' );

/** Estilos: tokens e componentes do design system, composição das páginas e ajustes aos blocos do WordPress. */
function tl_estilos() {
	$versao = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'tl-tokens', get_theme_file_uri( 'assets/css/tokens.css' ), array(), $versao );
	wp_enqueue_style( 'tl-componentes', get_theme_file_uri( 'assets/css/components.css' ), array( 'tl-tokens' ), $versao );
	wp_enqueue_style( 'tl-site', get_theme_file_uri( 'assets/css/site.css' ), array( 'tl-componentes' ), $versao );
	wp_enqueue_style( 'tl-wordpress', get_theme_file_uri( 'assets/css/wordpress.css' ), array( 'tl-site' ), $versao );
}
add_action( 'wp_enqueue_scripts', 'tl_estilos' );

/** Os mesmos estilos no editor, para os textos aparecerem como no site. */
function tl_estilos_editor() {
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/tokens.css', 'assets/css/components.css', 'assets/css/site.css', 'assets/css/wordpress.css' ) );
	add_post_type_support( 'page', 'excerpt' );
}
add_action( 'after_setup_theme', 'tl_estilos_editor' );

/** Categoria dos padrões do tema e estilos de botão do design system (escolhidos no editor, em Estilos do bloco Botão). */
function tl_registos_editor() {
	register_block_pattern_category( 'tantie-lorraine', array( 'label' => "Tantie Lorraine's" ) );
	register_block_style( 'core/button', array( 'name' => 'compacto', 'label' => 'Compacto' ) );
	register_block_style( 'core/button', array( 'name' => 'hero', 'label' => 'Hero' ) );
	register_block_style( 'core/button', array( 'name' => 'brevemente', 'label' => 'Brevemente (inativo)' ) );
}
add_action( 'init', 'tl_registos_editor' );

/** Língua da página: português de Portugal, mesmo que o WordPress esteja noutra língua. */
add_filter(
	'language_attributes',
	function () {
		return 'lang="pt-PT"';
	}
);

/** Descrição para motores de pesquisa: o resumo da página. */
function tl_meta_descricao() {
	if ( is_singular() && has_excerpt() ) {
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( get_the_excerpt() ) ) . '">' . "\n";
	} elseif ( is_front_page() ) {
		$inicio = get_post( get_option( 'page_on_front' ) );
		if ( $inicio && $inicio->post_excerpt ) {
			echo '<meta name="description" content="' . esc_attr( $inicio->post_excerpt ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'tl_meta_descricao', 1 );

/** Ícone do site: o sol do logótipo, enquanto não for definido outro em Aparência. */
function tl_icone() {
	if ( has_site_icon() ) {
		return;
	}
	echo '<link rel="icon" href="' . tl_img( 'favicon.png' ) . '" type="image/png">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . tl_img( 'apple-touch-icon.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'tl_icone' );

/**
 * Privacidade: sem pedidos a servidores externos.
 * Os emojis do WordPress carregam um script e imagens de s.w.org; desativados.
 */
function tl_sem_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'tl_sem_emojis' );

/** Sem comentários nem pingbacks num site de apresentação. */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'xmlrpc_enabled', '__return_false' );
