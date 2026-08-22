(() => {
    const nativeFetch = window.fetch.bind(window);

    window.fetch = async (...argumentsList) => {
        const response = await nativeFetch(...argumentsList);

        if (response.status !== 419) {
            return response;
        }

        try {
            const payload = await response.clone().json();

            if (payload?.code === 'SESSION_EXPIRED' && typeof payload.redirect === 'string') {
                window.location.replace(payload.redirect);
            }
        } catch {
            // Sólo las respuestas JSON contractuales de expiración provocan redirección.
        }

        return response;
    };
})();
