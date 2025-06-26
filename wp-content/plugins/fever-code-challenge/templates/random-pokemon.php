<?php
/**
 * Template for displaying a random Pokemon page.
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

		<div id="content" tabindex="-1" class="container-pokemon-random">

			<div class="row">
				<main class="site-main" id="main">

					<div class="pokemon-details"></div>
					<form method="post" action="<?php echo esc_url( home_url( '/generate' ) ); ?>">
						<button type="submit" class="button button-primary pokemon-random-button">
							<?php esc_html_e( 'Generate Another Random Pokemon', 'fever-code-challenge' ); ?>
					</form>

				</main>
			</div><!-- .row -->

		</div><!-- #content -->

	</div><!-- #page-wrapper -->

<?php
if ( function_exists( 'get_footer' ) ) {
	get_footer();
}
