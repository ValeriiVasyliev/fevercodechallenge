// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge'); // Optionally set if not loaded already
    var __ = wp.i18n.__;
}

const getFeverCodeChallengePokemonRandomSettings = () => {

    const restUrl = feverCodeChallengeFrontPokemonRandom.rest_url || '';
    return { restUrl };
};

document.addEventListener('DOMContentLoaded', () => {

    const button = document.querySelector('.pokemon-random-button');
    const container = document.querySelector('.pokemon-details');

    if (!container) {
        console.error('Container ".pokemon-details" not found.');
        return;
    }

    // Create or get image element
    let imageEl = container.querySelector('.pokemon-image');
    if (!imageEl) {
        imageEl = document.createElement('img');
        imageEl.className = 'pokemon-image';
        container.appendChild(imageEl);
    }

    // Create or get name element (h2)
    let nameEl = container.querySelector('h2');
    if (!nameEl) {
        nameEl = document.createElement('h2');
        container.appendChild(nameEl);
    }

    // Create or get content element (p)
    let contentEl = container.querySelector('p');
    if (!contentEl) {
        contentEl = document.createElement('p');
        container.appendChild(contentEl);
    }

    // Create or get a href element (a)
    let linkEl = container.querySelector('a');
    if (!linkEl) {
        linkEl = document.createElement('a');
        linkEl.className = 'pokemon-link';
        linkEl.textContent = 'View Details';
        linkEl.href = '#'; // Placeholder, will be updated later
        container.appendChild(linkEl);
    }

    const loadRandomPokemon = () => {

        const { restUrl } = getFeverCodeChallengePokemonRandomSettings();

        fetch(`${restUrl}?limit=1&order=rand`)
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    console.error('No Pokemon data found');
                    return;
                }

                const pokemon = data[0];
                console.log('Fetched Pokemon:', pokemon);

                imageEl.src = pokemon.featured_image || '';
                imageEl.alt = pokemon.name || 'Pokemon';

                nameEl.textContent = pokemon.name || 'Unknown';
                contentEl.textContent = pokemon.content || '';

                linkEl.href = pokemon.permalink || '#';
            })
            .catch(error => console.error('Error fetching random Pokemon:', error));
    };

    // Load on page load
    loadRandomPokemon();

    // Load on button click
    if (button) {
        button.addEventListener('click', event => {
            event.preventDefault();
            loadRandomPokemon();
        });
    } else {
        console.warn('Button ".pokemon-random-button" not found.');
    }
});