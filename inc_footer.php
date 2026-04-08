<?php
/**
 * Footer Template for London Labels
 * Include this at the bottom of each page
 */
?>
    </main>

    <footer class="main-footer">
        <div class="footer-inner">
            <div class="footer-content">
                <div class="footer-section footer-section-brand">
                    <a href="<?= BASE_URL ?>/index.php" class="footer-brand-link" aria-label="<?= e(SITE_NAME) ?> home">
                        <svg class="footer-brand-svg" viewBox="0 0 240 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="London Labels">
                            <defs>
                                <linearGradient id="footerUnderlineFade" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%"   stop-color="#e8357e" stop-opacity="0.9"/>
                                    <stop offset="70%"  stop-color="#e8357e" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#e8357e" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <text x="12" y="32"
                                font-family="Playfair Display, Georgia, serif"
                                font-size="28"
                                font-weight="400"
                                font-style="italic"
                                letter-spacing="0.5"
                                fill="#ffffff">London Labels</text>
                            <rect x="12" y="37" width="216" height="1.2" fill="url(#footerUnderlineFade)"/>
                        </svg>
                    </a>
                    <p class="footer-tagline"><?= e(SITE_TAGLINE) ?></p>
                    <p class="footer-store-locality">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        Treasure Mall, Abijo GRA, Ajah, Lagos
                    </p>
                    <a href="<?= e(STORE_MAP_URL) ?>" target="_blank" rel="noopener noreferrer" class="footer-store-cta">
                        Get Directions
                    </a>
                </div>

                <div class="footer-section">
                    <h4>Explore</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/shop.php">Shop</a></li>
                        <li><a href="<?= BASE_URL ?>/categories.php">Categories</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php">Contact Us</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="<?= BASE_URL ?>/account/orders.php">My Orders</a></li>
                        <?php else: ?>
                            <li><a href="<?= BASE_URL ?>/login.php">Sign In</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Information</h4>
                    <ul>
                        <li><a href="<?= BASE_URL ?>/legal/about.php">About Us</a></li>
                        <li><a href="<?= BASE_URL ?>/faq.php">FAQs</a></li>
                        <li><a href="<?= BASE_URL ?>/legal/privacy.php">Privacy Policy</a></li>
                        <li><a href="<?= BASE_URL ?>/legal/terms.php">Terms of Service</a></li>
                        <li><a href="<?= BASE_URL ?>/legal/cookies.php">Cookie Policy</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Community</h4>
                    <p class="footer-community-text">Join our community for new arrivals, style inspiration, and exclusive updates.</p>
                    <div class="footer-social-row">
                        <?php if (defined('WHATSAPP_GROUP_URL') && WHATSAPP_GROUP_URL !== ''): ?>
                            <a href="<?= e(WHATSAPP_GROUP_URL) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link footer-social-link-whatsapp" aria-label="Join our WhatsApp community">
                                <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M12 2a10 10 0 0 0-8.66 15l-1.27 4.63 4.76-1.25A10 10 0 1 0 12 2Zm0 18a7.95 7.95 0 0 1-4.08-1.12l-.29-.17-2.82.74.76-2.74-.19-.3A8 8 0 1 1 12 20Zm4.37-5.61c-.24-.12-1.41-.7-1.63-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.17-.71-.64-1.2-1.42-1.34-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.79-.2-.47-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2 0 1.18.86 2.32.98 2.48.12.16 1.69 2.58 4.1 3.61.57.25 1.02.4 1.37.51.58.18 1.11.15 1.53.09.47-.07 1.41-.58 1.61-1.15.2-.57.2-1.05.14-1.15-.06-.1-.22-.16-.46-.28Z"/>
                                </svg>
                                WhatsApp
                            </a>
                        <?php endif; ?>
                        <?php if (defined('YOUTUBE_CHANNEL_URL') && YOUTUBE_CHANNEL_URL !== ''): ?>
                            <a href="<?= e(YOUTUBE_CHANNEL_URL) ?>" target="_blank" rel="noopener noreferrer" class="footer-social-link footer-social-link-youtube" aria-label="Subscribe to our YouTube channel">
                                <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M23.5 7.2a3.14 3.14 0 0 0-2.2-2.22C19.35 4.5 12 4.5 12 4.5s-7.35 0-9.3.48A3.14 3.14 0 0 0 .5 7.2C0 9.17 0 12 0 12s0 2.83.5 4.8a3.14 3.14 0 0 0 2.2 2.22c1.95.48 9.3.48 9.3.48s7.35 0 9.3-.48a3.14 3.14 0 0 0 2.2-2.22C24 14.83 24 12 24 12s0-2.83-.5-4.8ZM9.6 15.02V8.98L15.4 12l-5.8 3.02Z"/>
                                </svg>
                                YouTube
                            </a>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/contact.php" class="footer-contact-link">Get in touch</a>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
                <p class="footer-bottom-tagline"><?= e(SITE_TAGLINE) ?></p>
            </div>
        </div>
    </footer>
    <script>
        (function () {
            var suggestEndpoint = '<?= BASE_URL ?>/search_suggest.php';
            var searchInputs = Array.prototype.slice.call(document.querySelectorAll('[data-autosuggest="products"]'));

            if (!searchInputs.length) {
                return;
            }

            searchInputs.forEach(function (input) {
                var listId = input.getAttribute('data-suggest-list');
                if (!listId) {
                    return;
                }
                var statusId = input.getAttribute('data-suggest-status') || (listId + '-status');

                var panel = document.getElementById(listId);
                if (!panel) {
                    return;
                }

                var statusRegion = document.getElementById(statusId);

                var activeIndex = -1;
                var items = [];
                var debounceTimer = null;
                var requestCounter = 0;
                var activeController = null;

                function announce(message) {
                    if (!statusRegion) {
                        return;
                    }
                    statusRegion.textContent = '';
                    statusRegion.textContent = String(message || '');
                }

                function closePanel(preserveAnnouncement) {
                    if (activeController) {
                        activeController.abort();
                        activeController = null;
                    }
                    panel.hidden = true;
                    panel.innerHTML = '';
                    input.setAttribute('aria-expanded', 'false');
                    input.removeAttribute('aria-activedescendant');
                    activeIndex = -1;
                    items = [];
                    if (!preserveAnnouncement) {
                        announce('');
                    }
                }

                function openPanel() {
                    panel.hidden = false;
                    input.setAttribute('aria-expanded', 'true');
                }

                function setActive(nextIndex) {
                    if (!items.length) {
                        activeIndex = -1;
                        return;
                    }

                    activeIndex = nextIndex;
                    if (activeIndex < 0) activeIndex = items.length - 1;
                    if (activeIndex >= items.length) activeIndex = 0;

                    items.forEach(function (node, index) {
                        node.classList.toggle('active', index === activeIndex);
                        node.setAttribute('aria-selected', index === activeIndex ? 'true' : 'false');
                    });

                    var activeNode = items[activeIndex];
                    if (activeNode && activeNode.id) {
                        input.setAttribute('aria-activedescendant', activeNode.id);
                        activeNode.scrollIntoView({ block: 'nearest' });

                        var activeTitle = activeNode.querySelector('.search-suggest-title');
                        var activeLabel = activeTitle ? activeTitle.textContent : '';
                        if (activeLabel) {
                            announce(activeLabel + ' selected');
                        }
                    } else {
                        input.removeAttribute('aria-activedescendant');
                    }
                }

                function appendHighlightedText(container, text, query) {
                    var sourceText = String(text || '');
                    var keyword = String(query || '').trim();

                    if (!keyword) {
                        container.textContent = sourceText;
                        return;
                    }

                    var sourceLower = sourceText.toLocaleLowerCase();
                    var keywordLower = keyword.toLocaleLowerCase();
                    var start = sourceLower.indexOf(keywordLower);

                    if (start < 0) {
                        container.textContent = sourceText;
                        return;
                    }

                    var end = start + keyword.length;
                    if (start > 0) {
                        container.appendChild(document.createTextNode(sourceText.slice(0, start)));
                    }

                    var mark = document.createElement('mark');
                    mark.className = 'search-suggest-match';
                    mark.textContent = sourceText.slice(start, end);
                    container.appendChild(mark);

                    if (end < sourceText.length) {
                        container.appendChild(document.createTextNode(sourceText.slice(end)));
                    }
                }

                function renderSuggestions(payload) {
                    var searchTerm = input.value.trim();
                    var groups = (payload && Array.isArray(payload.groups)) ? payload.groups : [];
                    var suggestions = (payload && Array.isArray(payload.items)) ? payload.items : [];
                    panel.innerHTML = '';
                    items = [];
                    activeIndex = -1;

                    if (!suggestions.length) {
                        panel.innerHTML = '<p class="search-suggest-empty">No matching products found.</p>';
                        announce('No matching products found');
                        openPanel();
                        return;
                    }

                    var groupsToRender = groups.length ? groups : [{ category: '', items: suggestions }];

                    groupsToRender.forEach(function (group) {
                        var groupItems = Array.isArray(group.items) ? group.items : [];
                        if (!groupItems.length) {
                            return;
                        }

                        if (group.category) {
                            var heading = document.createElement('div');
                            heading.className = 'search-suggest-group-heading';
                            appendHighlightedText(heading, group.category, searchTerm);
                            panel.appendChild(heading);
                        }

                        groupItems.forEach(function (suggestion) {
                            var optionId = listId + '-option-' + items.length;
                            var link = document.createElement('a');
                            link.href = suggestion.url;
                            link.id = optionId;
                            link.className = 'search-suggest-item';
                            link.setAttribute('role', 'option');
                            link.setAttribute('aria-selected', 'false');

                            var title = document.createElement('span');
                            title.className = 'search-suggest-title';
                            appendHighlightedText(title, suggestion.name, searchTerm);

                            var meta = document.createElement('span');
                            meta.className = 'search-suggest-meta';

                            var category = document.createElement('span');
                            appendHighlightedText(category, suggestion.category, searchTerm);

                            var price = document.createElement('span');
                            price.textContent = <?= json_encode(CURRENCY_SYMBOL) ?> + suggestion.price;

                            meta.appendChild(category);
                            meta.appendChild(price);

                            link.appendChild(title);
                            link.appendChild(meta);

                            panel.appendChild(link);
                            items.push(link);

                            link.addEventListener('mouseenter', function () {
                                var index = items.indexOf(link);
                                if (index >= 0) {
                                    setActive(index);
                                }
                            });

                            link.addEventListener('focus', function () {
                                var index = items.indexOf(link);
                                if (index >= 0) {
                                    setActive(index);
                                }
                            });
                        });
                    });

                    var countMessage = suggestions.length === 1
                        ? '1 suggestion available'
                        : suggestions.length + ' suggestions available';
                    announce(countMessage);
                    openPanel();
                }

                function fetchSuggestions(value) {
                    if (value.length < 2) {
                        closePanel();
                        return;
                    }

                    announce('Loading suggestions');

                    if (activeController) {
                        activeController.abort();
                        activeController = null;
                    }

                    var supportsAbort = typeof window.AbortController !== 'undefined';
                    activeController = supportsAbort ? new AbortController() : null;
                    var requestId = ++requestCounter;

                    fetch(suggestEndpoint + '?q=' + encodeURIComponent(value) + '&limit=8', {
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        signal: activeController ? activeController.signal : undefined
                    })
                        .then(function (response) {
                            if (response.status === 429) {
                                throw new Error('rate_limited');
                            }
                            if (!response.ok) {
                                throw new Error('Suggestion request failed');
                            }
                            return response.json();
                        })
                        .then(function (payload) {
                            if (requestId !== requestCounter) {
                                return;
                            }
                            if (input.value.trim() !== value) {
                                return;
                            }
                            renderSuggestions(payload);
                        })
                        .catch(function (error) {
                            if (error && error.name === 'AbortError') {
                                return;
                            }
                            if (error && error.message === 'rate_limited') {
                                announce('Too many requests. Please wait and try again.');
                                closePanel(true);
                                return;
                            }
                            announce('Suggestions temporarily unavailable');
                            closePanel(true);
                        })
                        .finally(function () {
                            if (requestId === requestCounter) {
                                activeController = null;
                            }
                        });
                }

                input.addEventListener('input', function () {
                    var value = input.value.trim();
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                    }
                    debounceTimer = setTimeout(function () {
                        fetchSuggestions(value);
                    }, 180);
                });

                input.addEventListener('keydown', function (event) {
                    if (panel.hidden) {
                        return;
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        setActive(activeIndex + 1);
                        return;
                    }

                    if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        setActive(activeIndex - 1);
                        return;
                    }

                    if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
                        event.preventDefault();
                        window.location.href = items[activeIndex].href;
                        return;
                    }

                    if (event.key === 'Escape') {
                        closePanel();
                    }
                });

                document.addEventListener('click', function (event) {
                    if (event.target === input || panel.contains(event.target)) {
                        return;
                    }
                    closePanel();
                });

                input.form && input.form.addEventListener('submit', function () {
                    closePanel();
                });
            });
        })();
    </script>
</body>
</html>
