<?php
/**
 * Title: Página inicial
 * Slug: tantie-lorraine/pagina-inicio
 * Categories: tantie-lorraine
 * Inserter: no
 *
 * @package tantie-lorraine
 */

?>
<!-- wp:cover {"url":"<?php echo tl_img( 'hero.webp' ); ?>","dimRatio":40,"overlayColor":"ink","minHeight":560,"className":"hero","layout":{"type":"default"}} -->
<div class="wp-block-cover hero" style="min-height:560px"><img class="wp-block-cover__image-background" alt="" src="<?php echo tl_img( 'hero.webp' ); ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":1,"className":"tl-text-hero-mega"} -->
<h1 class="wp-block-heading tl-text-hero-mega">Chilli Tings</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-subhead"} -->
<p class="tl-text-subhead">Compotas de malagueta com sabores de Trindade e Tobago. Brevemente em Portugal.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-hero"} -->
<div class="wp-block-button is-style-hero"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>">Ver produtos</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"tagName":"section","className":"seccao centrado","layout":{"type":"default"}} -->
<section class="wp-block-group seccao centrado"><!-- wp:group {"className":"contentor","layout":{"type":"default"}} -->
<div class="wp-block-group contentor"><!-- wp:heading {"className":"tl-text-statement"} -->
<h2 class="wp-block-heading tl-text-statement">Sabores das Caraíbas, feitos à mão em Surrey</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-body texto"} -->
<p class="tl-text-body texto">Compotas de malagueta feitas em pequenos lotes, com ingredientes frescos e naturais. Sem conservantes nem aditivos artificiais.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"seccao seccao--mist","layout":{"type":"default"}} -->
<section class="wp-block-group seccao seccao--mist"><!-- wp:group {"className":"contentor","layout":{"type":"default"}} -->
<div class="wp-block-group contentor"><!-- wp:heading {"className":"tl-text-section-title centrado"} -->
<h2 class="wp-block-heading tl-text-section-title centrado">A gama</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"gama"} -->
<div class="wp-block-columns gama"><!-- wp:column {"className":"cartao"} -->
<div class="wp-block-column cartao"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"cartao__foto"} -->
<figure class="wp-block-image size-full cartao__foto"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#sbj"><img src="<?php echo tl_img( 'produtos/sbj.webp' ); ?>" alt="Frasco de Scotch Bonnet Jam"/></a></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"className":"tl-card__title"} -->
<h3 class="wp-block-heading tl-card__title"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#sbj">Scotch Bonnet Jam</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-ui-sm ref"} -->
<p class="tl-text-ui-sm ref">Ref. SBJ</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-brevemente"} -->
<div class="wp-block-button is-style-brevemente"><a class="wp-block-button__link wp-element-button">Brevemente</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --><!-- wp:column {"className":"cartao"} -->
<div class="wp-block-column cartao"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"cartao__foto"} -->
<figure class="wp-block-image size-full cartao__foto"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#acj"><img src="<?php echo tl_img( 'produtos/acj.webp' ); ?>" alt="Frasco de Apple Chilli Jam"/></a></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"className":"tl-card__title"} -->
<h3 class="wp-block-heading tl-card__title"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#acj">Apple Chilli Jam</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-ui-sm ref"} -->
<p class="tl-text-ui-sm ref">Ref. ACJ</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-brevemente"} -->
<div class="wp-block-button is-style-brevemente"><a class="wp-block-button__link wp-element-button">Brevemente</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --><!-- wp:column {"className":"cartao"} -->
<div class="wp-block-column cartao"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"cartao__foto"} -->
<figure class="wp-block-image size-full cartao__foto"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#rpcl"><img src="<?php echo tl_img( 'produtos/rpcl.webp' ); ?>" alt="Frasco de Red Pepper Chilli Lime Jam"/></a></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"className":"tl-card__title"} -->
<h3 class="wp-block-heading tl-card__title"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#rpcl">Red Pepper Chilli Lime Jam</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-ui-sm ref"} -->
<p class="tl-text-ui-sm ref">Ref. RPCL</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-brevemente"} -->
<div class="wp-block-button is-style-brevemente"><a class="wp-block-button__link wp-element-button">Brevemente</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --><!-- wp:column {"className":"cartao"} -->
<div class="wp-block-column cartao"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"cartao__foto"} -->
<figure class="wp-block-image size-full cartao__foto"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#tcj"><img src="<?php echo tl_img( 'produtos/tcj.webp' ); ?>" alt="Frasco de Tomato Chilli Jam"/></a></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"className":"tl-card__title"} -->
<h3 class="wp-block-heading tl-card__title"><a href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>#tcj">Tomato Chilli Jam</a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-ui-sm ref"} -->
<p class="tl-text-ui-sm ref">Ref. TCJ</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-brevemente"} -->
<div class="wp-block-button is-style-brevemente"><a class="wp-block-button__link wp-element-button">Brevemente</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-compacto"} -->
<div class="wp-block-button is-style-compacto"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( tl_url( 'produtos' ) ); ?>">Conhecer a gama</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"seccao","layout":{"type":"default"}} -->
<section class="wp-block-group seccao"><!-- wp:group {"className":"contentor","layout":{"type":"default"}} -->
<div class="wp-block-group contentor"><!-- wp:columns {"className":"duas-colunas"} -->
<div class="wp-block-columns duas-colunas"><!-- wp:column {"className":"texto"} -->
<div class="wp-block-column texto"><!-- wp:heading {"className":"tl-text-section-title"} -->
<h2 class="wp-block-heading tl-text-section-title">Quem é a Tantie?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"tl-text-body"} -->
<p class="tl-text-body">Lorraine nasceu e cresceu em Trindade e Tobago, criada por tias e avós. Em Surrey, onde vive, transformou as receitas de casa numa marca artesanal premiada.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"tl-text-body"} -->
<p class="tl-text-body"><a href="<?php echo esc_url( tl_url( 'historia' ) ); ?>">Conhecer a história</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"foto-reservada"} -->
<figure class="wp-block-image size-full foto-reservada"><img src="<?php echo tl_img( 'inicio-tantie.webp' ); ?>" alt="Lorraine, fundadora da Tantie Lorraine's"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->
