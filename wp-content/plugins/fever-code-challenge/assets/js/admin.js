// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'fever-code-challenge'); // Optionally set if not loaded already
    var __ = wp.i18n.__;
}

// Get Fever Code Challenge plugin settings
const getFeverCodeChallengeSettings = () => {
    const baseUrl = feverCodeChallengeAdmin?.ajax_url ?? '';
    const nonce = feverCodeChallengeAdmin?.nonce ?? '';
    return { baseUrl, nonce };
};

document.addEventListener('DOMContentLoaded', () => {
    const button = document.querySelector('.create-pokemon-ajax');
    if (!button) return;

    button.addEventListener('click', (e) => {
        e.preventDefault();

        const { baseUrl, nonce } = getFeverCodeChallengeSettings();

        if (!baseUrl || !nonce) {
            console.error(__('Missing base URL or nonce.', 'fever-code-challenge'));
            return;
        }

        const action = button.getAttribute('data-action');

        fetch(baseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: new URLSearchParams({
                action: action,
                nonce: nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert(__('Pokemon created successfully!', 'fever-code-challenge'));

                    // refresh the page to show the new Pokemon
                    window.location.reload();
                } else {
                    const message = data.data?.message || __('Unknown error', 'fever-code-challenge');
                    alert(__('Failed to create Pokemon:', 'fever-code-challenge') + ' ' + message);
                }
            })
            .catch((error) => {
                console.error(__('AJAX error:', 'fever-code-challenge'), error);
                alert(__('AJAX request failed.', 'fever-code-challenge'));
            });
    });
});
