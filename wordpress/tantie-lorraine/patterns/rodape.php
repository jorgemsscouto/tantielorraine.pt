<?php
/**
 * Title: Rodapé
 * Slug: tantie-lorraine/rodape
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package tantie-lorraine
 */

$tl_links = array(
	'historia'    => 'História',
	'produtos'    => 'Produtos',
	'contactos'   => 'Contactos',
	'privacidade' => 'Privacidade',
);
?>
<!-- wp:group {"tagName":"footer","className":"tl-footer","layout":{"type":"default"}} -->
<footer class="wp-block-group tl-footer"><!-- wp:group {"className":"tl-footer__links","layout":{"type":"default"}} -->
<div class="wp-block-group tl-footer__links"><?php foreach ( $tl_links as $tl_slug => $tl_rotulo ) : ?><!-- wp:paragraph -->
<p><a href="<?php echo esc_url( tl_url( $tl_slug ) ); ?>"><?php echo esc_html( $tl_rotulo ); ?></a></p>
<!-- /wp:paragraph -->

<?php endforeach; ?><!-- wp:paragraph -->
<p><a href="https://www.livroreclamacoes.pt" target="_blank" rel="noreferrer noopener">Livro de Reclamações</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tl-footer__social","layout":{"type":"default"}} -->
<div class="wp-block-group tl-footer__social"><!-- wp:paragraph {"className":"tl-text-nav"} -->
<p class="tl-text-nav"><a href="https://www.instagram.com/tantielorraine/" target="_blank" rel="noreferrer noopener">Instagram</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"tl-footer__bottom","layout":{"type":"default"}} -->
<div class="wp-block-group tl-footer__bottom"><!-- wp:image {"width":"161px","sizeSlug":"full","linkDestination":"custom","className":"marca"} -->
<figure class="wp-block-image size-full is-resized marca"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo tl_img( 'logo.png' ); ?>" alt="Tantie Lorraine's" style="width:161px;height:auto"/></a></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"tl-footer__legal"} -->
<p class="tl-footer__legal">TANTIE LORRAINE ® <?php echo esc_html( gmdate( 'Y' ) ); ?> TODOS OS DIREITOS RESERVADOS</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
