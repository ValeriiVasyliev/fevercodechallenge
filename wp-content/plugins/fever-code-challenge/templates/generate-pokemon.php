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

		<div id="content" tabindex="-1" class="container-pokemon-generate">

			<div class="row">
				<main class="site-main" id="main">
					<?php
					if ( ! ( current_user_can( 'edit_posts' ) ) ) :
						?>
						<div class="alert alert-warning">
							<?php esc_html_e( 'You do not have permission to generate Pokemon. Please log in with an account that has the appropriate permissions.', 'fever-code-challenge' ); ?>
						</div>
					<?php else : ?>
						<div class="pokemon-details"></div>
						<form method="post" action="<?php echo esc_url( home_url( '/generate' ) ); ?>">
							<button type="submit" class="button button-primary pokemon-generate-button">
								<?php esc_html_e( 'Generate Pokemon from API', 'fever-code-challenge' ); ?>
						</form>
					<?php endif; ?>
				</main>
			</div><!-- .row -->

		</div><!-- #content -->

	</div><!-- #page-wrapper -->

<?php
if ( function_exists( 'get_footer' ) ) {
	get_footer();
}
