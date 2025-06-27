<?php
/**
 * Template for displaying a Pokémon list page with pagination and type filter.
 *
 * @package fever-code-challenge
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'get_header' ) ) {
	get_header();
}
?>

<div class="wrapper pokemon-list-center" id="page-wrapper">
	<div id="content" tabindex="-1" class="container-pokemon-list">
		<div class="row">
			<main class="site-main" id="main">

				<!-- Type Filter -->
				<form method="get" class="pokemon-filter-form" role="search" aria-label="<?php esc_attr_e( 'Filter Pokémon by Type', 'fever-code-challenge' ); ?>">
					<label for="pokemon-filter-type">
						<?php esc_html_e( 'Filter by Type:', 'fever-code-challenge' ); ?>
					</label>
					<select name="pokemon-filter-type" id="pokemon-filter-type">
						<option value=""><?php esc_html_e( 'All Types', 'fever-code-challenge' ); ?></option>
						<?php
						foreach ( $unique_types as $unique_type ) {
							printf(
								'<option value="%s">%s</option>',
								esc_attr( $unique_type ),
								esc_html( $unique_type )
							);
						}
						?>
					</select>
				</form>

				<!-- Pokémon List -->
				<div class="pokemon-list-wrapper">
					<div class="pokemon-list">
						<!-- Pokémon items rendered here -->
					</div>
				</div>

				<!-- Pagination -->
				<div class="pokemon-pagination">
					<!-- Pagination controls rendered here -->
				</div>

			</main>
		</div>
	</div>
</div>

<?php
if ( function_exists( 'get_footer' ) ) {
	get_footer();
}
