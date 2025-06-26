<?php
/**
 * Template to generate a random Pokemon.
 *
 * @package fever-code-challenge
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( function_exists( 'get_header' ) ) {
	get_header();
}
?>

	<div class="wrapper" id="page-wrapper">

		<div id="content" tabindex="-1">

			<div class="row">
				<main class="site-main" id="main">
					Generate a random Pokémon
				</main>
			</div><!-- .row -->

		</div><!-- #content -->

	</div><!-- #page-wrapper -->

<?php
if ( function_exists( 'get_footer' ) ) {
	get_footer();
}
