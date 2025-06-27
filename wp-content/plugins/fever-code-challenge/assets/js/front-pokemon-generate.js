// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge');
    var __ = wp.i18n.__;
}

// Utility to get plugin settings
const getFeverCodeChallengeSettings = () => ({
    baseUrl: feverCodeChallengeFrontPokemonGenerate?.ajax_url ?? '',
    nonce: feverCodeChallengeFrontPokemonGenerate?.nonce ?? '',
});

// Ensure an element exists or create it
const ensureElement = (selector, tagName, className, parent) => {
    let el = parent.querySelector(selector);
    if (!el) {
        el = document.createElement(tagName);
        if (className) el.className = className;
        parent.appendChild(el);
    }
    return el;
};

// Load and display a new Pokemon
const loadGeneratePokemon = () => {
    const { baseUrl, nonce } = getFeverCodeChallengeSettings();

    if (!baseUrl || !nonce) {
        console.error(__('Missing base URL or nonce.', 'fever-code-challenge'));
        return;
    }

    const action = 'create_new_pokemon';
    const container = document.querySelector('.pokemon-details');

    if (!container) {
        console.error(__('Container ".pokemon-details" not found.', 'fever-code-challenge'));
        return;
    }

    const imageEl = ensureElement('.pokemon-image', 'img', 'pokemon-image', container);
    const nameEl = ensureElement('h2', 'h2', '', container);
    const contentEl = ensureElement('p', 'p', '', container);
    const linkEl = ensureElement('a.pokemon-link', 'a', 'pokemon-link', container);

    fetch(baseUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: new URLSearchParams({ action, nonce }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (!data.success || !data.data?.pokemon) {
                const message = data.data?.message || __('Unknown error', 'fever-code-challenge');
                console.error(__('Failed to create Pokemon:', 'fever-code-challenge'), message);
                alert(__('Failed to create Pokemon:', 'fever-code-challenge') + ' ' + message);
                return;
            }

            const { name, description, featured_image, permalink } = data.data.pokemon;

            console.log('Created & Fetched Pokemon:', data.data.pokemon);

            imageEl.src = featured_image || '';
            imageEl.alt = name || 'Pokemon';
            nameEl.textContent = name || __('Unknown', 'fever-code-challenge');
            contentEl.textContent = description || '';
            linkEl.href = permalink || '#';
            linkEl.textContent = __('View Details', 'fever-code-challenge');
        })
        .catch((error) => {
            console.error(__('AJAX error:', 'fever-code-challenge'), error);
            alert(__('AJAX request failed.', 'fever-code-challenge'));
        });
};

// Setup event listeners after DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('.pokemon-generate-button');

    if (button) {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            loadGeneratePokemon();
        });
    } else {
        console.warn(__('Button ".pokemon-generate-button" not found.', 'fever-code-challenge'));
    }
});
