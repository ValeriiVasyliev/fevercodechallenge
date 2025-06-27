// front-pokemon-list.ts

// Initialize i18n localization
let __: (text: string, domain?: string) => string = (text) => text;
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge');
    __ = wp.i18n.__;
}

interface Pokemon {
    name: string;
    permalink: string;
    featured_image: string;
}

interface ApiResponse {
    data: Pokemon[];
    total: number;
    pages: number;
}

const getFeverPokemonListSettings = () => ({
    restUrl: feverPokemonList?.rest_url || '',
    perPage: feverPokemonList?.per_page || 10,
});

const createPokemonItem = (pokemon: Partial<Pokemon>): HTMLElement => {
    const container = document.createElement('div');
    container.className = 'pokemon-item';

    const link = document.createElement('a');
    link.href = pokemon.permalink || '#';

    const img = document.createElement('img');
    img.src = pokemon.featured_image || '';
    img.alt = pokemon.name || __('Pokemon', 'fever-code-challenge');

    const title = document.createElement('h2');
    const nameLink = document.createElement('a');
    nameLink.href = pokemon.permalink || '#';
    nameLink.textContent = pokemon.name || __('Unknown', 'fever-code-challenge');

    title.appendChild(nameLink);
    link.appendChild(img);

    container.appendChild(link);
    container.appendChild(title);

    return container;
};

const renderPagination = (
    container: HTMLElement,
    totalPages: number,
    currentPage: number,
    selectedType: string
): void => {
    container.innerHTML = '';
    if (totalPages <= 1) return;

    for (let page = 1; page <= totalPages; page++) {
        const btn = document.createElement('button');
        btn.textContent = page.toString();
        btn.className = 'pokemon-page-button' + (page === currentPage ? ' current' : '');

        btn.addEventListener('click', () => {
            loadFilteredPokemon(selectedType, page);
        });

        container.appendChild(btn);
    }
};

const loadFilteredPokemon = (type = '', page = 1): void => {
    const { restUrl, perPage } = getFeverPokemonListSettings();
    const listContainer = document.querySelector<HTMLElement>('.pokemon-list');
    const paginationContainer = document.querySelector<HTMLElement>('.pokemon-pagination');

    if (!restUrl || !listContainer) {
        console.error(__('Missing REST URL or container.', 'fever-code-challenge'));
        return;
    }

    listContainer.innerHTML = `<p>${__('Loading...', 'fever-code-challenge')}</p>`;
    if (paginationContainer) paginationContainer.innerHTML = '';

    const params = new URLSearchParams({
        pokemon_type: type,
        page: page.toString(),
        limit: perPage.toString(),
    });

    fetch(`${restUrl}?${params.toString()}`)
        .then((response) => response.json())
        .then((result: ApiResponse) => {
            const { data, pages } = result;
            listContainer.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                listContainer.innerHTML = `<p>${__('No Pokémon found.', 'fever-code-challenge')}</p>`;
                return;
            }

            data.forEach((pokemon) => {
                const item = createPokemonItem(pokemon);
                listContainer.appendChild(item);
            });

            if (paginationContainer && pages > 1) {
                renderPagination(paginationContainer, pages, page, type);
            }
        })
        .catch((error) => {
            console.error(__('Failed to load Pokémon.', 'fever-code-challenge'), error);
            listContainer.innerHTML = `<p>${__('Error loading Pokémon.', 'fever-code-challenge')}</p>`;
        });
};

document.addEventListener('DOMContentLoaded', () => {
    const select = document.querySelector<HTMLSelectElement>('#pokemon-filter-type');
    if (!select) {
        console.warn(__('Select "#pokemon-filter-type" not found.', 'fever-code-challenge'));
        return;
    }

    loadFilteredPokemon(select.value);

    select.addEventListener('change', (event: Event) => {
        const selectedType = (event.target as HTMLSelectElement).value;
        loadFilteredPokemon(selectedType, 1); // reset to first page
    });
});
