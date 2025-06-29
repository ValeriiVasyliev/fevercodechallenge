// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge');
    var __ = wp.i18n.__;
}

const getFeverCodeChallengeFrontPokemonSettings = () => ({
    restUrl: feverCodeChallengeFrontPokemon?.rest_url ?? '',
});

/**
 * Load the oldest Pokémon, optionally scoped by a specific Pokémon ID.
 *
 * @param {string|null} pokemonId The ID of the Pokémon to pass as a parameter.
 */
const loadOldestPokemon = async (pokemonId = null) => {
    const { restUrl } = getFeverCodeChallengeFrontPokemonSettings();

    if (!restUrl) {
        console.error(__('Missing REST URL.', 'fever-code-challenge'));
        return;
    }

    const url = new URL(`${restUrl}/${pokemonId}`);

    try {
        const res = await fetch(url.toString());
        const contentType = res.headers.get('content-type') || '';
        const isJson = contentType.includes('application/json');
        const data = isJson ? await res.json() : null;

        if (!res.ok) {
            const errorMessage = data?.message || __('Unexpected server error.', 'fever-code-challenge');
            console.error(__('Server responded with error:', 'fever-code-challenge'), errorMessage);
            alert(`${__('Failed to load Pokémon:', 'fever-code-challenge')} ${errorMessage}`);
            return;
        }

        if (!data?.id || !data?.name) {
            const fallbackMsg = __('Invalid Pokémon data received.', 'fever-code-challenge');
            console.error(fallbackMsg, data);
            alert(fallbackMsg);
            return;
        }

        const pokemonOldestDex = document.querySelector('.pokemon-oldest-dex');
        const pokemonCustomFields = document.querySelector('.pokemon-custom-fields');

        if (pokemonOldestDex && pokemonCustomFields) {

            // Display oldest dex data if available
            if (data.oldest_dex) {
                const oldestDexData = document.createElement('p');
                oldestDexData.innerHTML = `<strong>${__('Oldest Dex', 'fever-code-challenge')}:</strong> ${data.oldest_dex}`;
                pokemonCustomFields.appendChild(oldestDexData);
            }

            // Display oldest dex name if available
            if (data.oldest_dex_name) {
                const oldestDexName = document.createElement('p');
                oldestDexName.innerHTML = `<strong>${__('Oldest Dex Entry', 'fever-code-challenge')}:</strong> ${data.oldest_dex_name}`;
                pokemonCustomFields.appendChild(oldestDexName);
            }

            // Remove the .pokemon-oldest-dex element (after appending content)
            pokemonOldestDex.remove();
        }

    } catch (error) {
        console.error(__('Network or parsing error:', 'fever-code-challenge'), error);
        alert(__('AJAX request failed. Please check your connection or try again later.', 'fever-code-challenge'));
    }
};

/**
 * Attach click event to the button to load the oldest Pokémon.
 */
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('button.pokemon-oldest-dex-button');

    if (button) {
        const pokemonId = button.getAttribute('data-id');
        button.addEventListener('click', () => loadOldestPokemon(pokemonId));
    } else {
        console.warn(__('Button ".pokemon-oldest-dex-button" not found.', 'fever-code-challenge'));
    }
});
