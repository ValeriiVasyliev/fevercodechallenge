// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge');
    var __ = wp.i18n.__;
}

// Get REST settings
const getFeverCodeChallengePokemonRandomSettings = () => ({
    restUrl: feverCodeChallengeFrontPokemonRandom?.rest_url || '',
});

// Ensure an element exists or create and append it
const ensureElement = (selector, tagName, className, parent) => {
    let el = parent.querySelector(selector);
    if (!el) {
        el = document.createElement(tagName);
        if (className) el.className = className;
        parent.appendChild(el);
    }
    return el;
};

// Load and display a random Pokemon
const loadRandomPokemon = (container) => {
    const { restUrl } = getFeverCodeChallengePokemonRandomSettings();
    if (!restUrl) {
        console.error(__('REST URL is missing.', 'fever-code-challenge'));
        return;
    }

    fetch(`${restUrl}?limit=1&order=rand`)
        .then((response) => response.json())
        .then((data) => {
            if (!Array.isArray(data) || data.length === 0) {
                console.error(__('No Pokemon data found.', 'fever-code-challenge'));
                return;
            }

            const pokemon = data[0];
            console.log('Fetched Pokemon:', pokemon);

            const imageEl = ensureElement('.pokemon-image', 'img', 'pokemon-image', container);
            const nameEl = ensureElement('h2', 'h2', '', container);
            const contentEl = ensureElement('p', 'p', '', container);
            const linkEl = ensureElement('a.pokemon-link', 'a', 'pokemon-link', container);

            imageEl.src = pokemon.featured_image || '';
            imageEl.alt = pokemon.name || 'Pokemon';
            nameEl.textContent = pokemon.name || __('Unknown', 'fever-code-challenge');
            contentEl.textContent = pokemon.content || '';
            linkEl.href = pokemon.permalink || '#';
            linkEl.textContent = __('View Details', 'fever-code-challenge');
        })
        .catch((error) => {
            console.error(__('Error fetching random Pokemon:', 'fever-code-challenge'), error);
        });
};

// DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('.pokemon-random-button');
    const container = document.querySelector('.pokemon-details');

    if (!container) {
        console.error(__('Container ".pokemon-details" not found.', 'fever-code-challenge'));
        return;
    }

    // Load on page load
    loadRandomPokemon(container);

    // Load on button click
    if (button) {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            loadRandomPokemon(container);
        });
    } else {
        console.warn(__('Button ".pokemon-random-button" not found.', 'fever-code-challenge'));
    }
});
