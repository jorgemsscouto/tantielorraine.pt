<?php
/**
 * Title: Cabeçalho
 * Slug: tantie-lorraine/cabecalho
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 *
 * @package tantie-lorraine
 */

$tl_menu = array(
	'historia'  => 'História',
	'produtos'  => 'Produtos',
	'eventos'   => 'Eventos',
	'contactos' => 'Contactos',
);
?>
<!-- wp:group {"tagName":"header","className":"tl-header","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<header class="wp-block-group tl-header"><!-- wp:image {"width":"105px","sizeSlug":"full","linkDestination":"custom","className":"marca"} -->
<figure class="wp-block-image size-full is-resized marca"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo tl_img( 'logo.png' ); ?>" alt="Tantie Lorraine's" style="width:105px;height:auto"/></a></figure>
<!-- /wp:image -->

<!-- wp:navigation {"overlayMenu":"mobile","hasIcon":false,"overlayBackgroundColor":"chilli-red","overlayTextColor":"ink","className":"menu-principal","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"}} -->
<?php
foreach ( $tl_menu as $tl_slug => $tl_rotulo ) {
	$tl_pagina = get_page_by_path( $tl_slug );
	$tl_attrs  = array(
		'label' => $tl_rotulo,
		'url'   => tl_url( $tl_slug ),
	);
	if ( $tl_pagina ) {
		$tl_attrs['type'] = 'page';
		$tl_attrs['id']   = $tl_pagina->ID;
		$tl_attrs['kind'] = 'post-type';
	} else {
		$tl_attrs['kind'] = 'custom';
	}
	echo '<!-- wp:navigation-link ' . wp_json_encode( $tl_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->' . "\n";
}
?>
<!-- /wp:navigation --></header>
<!-- /wp:group -->
