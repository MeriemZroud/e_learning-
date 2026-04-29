document.addEventListener('DOMContentLoaded', function () {
    const languageConfig = {
        fr: { flag: '🇫🇷' },
        en: { flag: '🇬🇧' },
        ar: { flag: '🇸🇦' },
        es: { flag: '🇪🇸' },
        de: { flag: '🇩🇪' }
    };

    const translationCache = JSON.parse(sessionStorage.getItem('translationCache') || '{}');
    let currentLang = localStorage.getItem('selectedLanguage') || 'en';

    function ensureSelectorExists() {
        const selector = document.querySelector('.language-selector');
        if (selector) {
            return selector;
        }

        const mountPoint = document.querySelector('.student-topbar__actions')
            || document.querySelector('.learnway-actions')
            || document.querySelector('.admin-profile-header')
            || document.querySelector('[class*="page_actions"]')
            || document.querySelector('header')
            || document.body;

        const wrapper = document.createElement('div');
        wrapper.className = 'language-selector';
        wrapper.innerHTML = `
            <button class="language-btn" type="button" title="Choose language">
                <span class="current-lang">🌐 EN</span>
                <svg width="12" height="8" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 1L6 6L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="language-dropdown">
                <button class="language-option" data-lang="fr" type="button">🇫🇷 Français</button>
                <button class="language-option" data-lang="en" type="button">🇬🇧 English</button>
                <button class="language-option" data-lang="ar" type="button">🇸🇦 العربية</button>
                <button class="language-option" data-lang="es" type="button">🇪🇸 Español</button>
                <button class="language-option" data-lang="de" type="button">🇩🇪 Deutsch</button>
            </div>
        `;
        mountPoint.prepend(wrapper);
        return wrapper;
    }

    const selector = ensureSelectorExists();
    const btn = selector.querySelector('.language-btn');
    const dropdown = selector.querySelector('.language-dropdown');
    const options = selector.querySelectorAll('.language-option');

    if (!btn || !dropdown || options.length === 0) {
        return;
    }

    function updateLanguageDisplay(lang) {
        const current = selector.querySelector('.current-lang') || selector.querySelector('#currentLang');
        const config = languageConfig[lang] || languageConfig.en;
        if (current) {
            current.textContent = `${config.flag} ${String(lang).toUpperCase()}`;
        }
        options.forEach((option) => {
            option.classList.toggle('active', option.dataset.lang === lang);
        });
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        btn.classList.toggle('active');
        dropdown.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
        if (!selector.contains(e.target)) {
            btn.classList.remove('active');
            dropdown.classList.remove('active');
        }
    });

    options.forEach((option) => {
        option.addEventListener('click', async function (e) {
            e.preventDefault();
            const selectedLang = this.dataset.lang;
            if (!selectedLang || selectedLang === currentLang) {
                dropdown.classList.remove('active');
                btn.classList.remove('active');
                return;
            }

            currentLang = selectedLang;
            localStorage.setItem('selectedLanguage', currentLang);
            await translatePage(currentLang);
            dropdown.classList.remove('active');
            btn.classList.remove('active');
        });
    });

    updateLanguageDisplay(currentLang);

    async function translatePage(targetLang) {
        if (targetLang === 'en') {
            location.reload();
            return;
        }

        try {
            document.body.classList.add('translating');

            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
            const textNodes = [];
            let node;

            while ((node = walker.nextNode())) {
                const parent = node.parentElement;
                if (!parent) {
                    continue;
                }
                if (parent.closest('.language-selector')) {
                    continue;
                }

                const text = node.textContent.trim();
                if (!text || text.length < 2 || text.length > 300) {
                    continue;
                }
                if (/^\d+\.?\d*%?$/.test(text)) {
                    continue;
                }

                textNodes.push({ node, text });
            }

            if (textNodes.length === 0) {
                updateLanguageDisplay(targetLang);
                return;
            }

            const textsToTranslate = textNodes.map((item) => item.text);
            const cacheKey = `${targetLang}:${textsToTranslate.join('|').substring(0, 200)}`;
            let translations = translationCache[cacheKey];

            if (!translations) {
                translations = [];
                const chunkSize = 20;

                for (let i = 0; i < textsToTranslate.length; i += chunkSize) {
                    const chunk = textsToTranslate.slice(i, i + chunkSize);
                    const response = await fetch('/api/translate-batch', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            texts: chunk,
                            sourceLang: 'en',
                            targetLang: targetLang
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`API error: ${response.status}`);
                    }

                    const data = await response.json();
                    if (!data.success || !Array.isArray(data.translations)) {
                        throw new Error('Invalid translation response');
                    }

                    translations = translations.concat(data.translations);
                }

                translationCache[cacheKey] = translations;
                sessionStorage.setItem('translationCache', JSON.stringify(translationCache));
            }

            textNodes.forEach((item, index) => {
                if (index < translations.length) {
                    item.node.textContent = translations[index];
                }
            });

            document.documentElement.lang = targetLang;
            updateLanguageDisplay(targetLang);
        } catch (error) {
            console.error('Language translation failed:', error);
        } finally {
            document.body.classList.remove('translating');
        }
    }
});
