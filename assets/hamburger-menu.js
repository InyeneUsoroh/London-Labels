/**
 * Mobile Nav — Slide-in Drawer
 */
class MobileNav {
    constructor() {
        this.drawerBtn   = document.getElementById('mobDrawerBtn');
        this.drawerClose = document.getElementById('mobDrawerClose');
        this.drawer      = document.getElementById('mobDrawer');
        this.overlay     = document.getElementById('navOverlay');
        this.searchBtn   = document.getElementById('mobSearchBtn');
        this.searchBar   = document.getElementById('mobSearchBar');
        this.searchInput = this.searchBar ? this.searchBar.querySelector('input') : null;
        this.shopToggle  = document.getElementById('mobShopToggle');
        this.categoriesSub = document.getElementById('mobCategoriesSub');
        this.isDrawerOpen  = false;
        this.isSearchOpen  = false;
        this.categoriesLoaded = false;

        this.init();
    }

    init() {
        if (!this.drawerBtn || !this.drawer) return;

        this.drawerBtn.addEventListener('click', () => this.openDrawer());
        this.drawerClose && this.drawerClose.addEventListener('click', () => this.closeDrawer());
        this.overlay && this.overlay.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.isDrawerOpen) this.closeDrawer();
            if (this.isSearchOpen) this.closeSearch();
        });

        this.searchBtn && this.searchBtn.addEventListener('click', () => {
            this.isSearchOpen ? this.closeSearch() : this.openSearch();
        });

        this.searchBar && this.searchBar.addEventListener('submit', (e) => {
            const val = this.searchInput ? this.searchInput.value.trim() : '';
            if (!val) {
                e.preventDefault();
                this.closeSearch();
            }
        });

        this.shopToggle && this.shopToggle.addEventListener('click', () => {
            this.toggleCategories();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (this.isDrawerOpen) this.closeDrawer();
                if (this.isSearchOpen) this.closeSearch();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                if (this.isDrawerOpen) this.closeDrawer();
                if (this.isSearchOpen) this.closeSearch();
            }
        });

        this.loadCategories();
    }

    openDrawer() {
        this.isDrawerOpen = true;
        this.drawer.classList.add('is-open');
        this.drawer.setAttribute('aria-hidden', 'false');
        this.drawerBtn.classList.add('is-open');
        this.drawerBtn.setAttribute('aria-expanded', 'true');
        this.overlay && this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        const first = this.drawer.querySelector('button, a');
        if (first) setTimeout(() => first.focus(), 50);
    }

    closeDrawer() {
        this.isDrawerOpen = false;
        this.drawer.classList.remove('is-open');
        this.drawer.setAttribute('aria-hidden', 'true');
        this.drawerBtn.classList.remove('is-open');
        this.drawerBtn.setAttribute('aria-expanded', 'false');
        this.overlay && this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        this.drawerBtn.focus();
    }

    openSearch() {
        this.isSearchOpen = true;
        this.searchBar && this.searchBar.classList.add('active');
        this.searchBtn && this.searchBtn.setAttribute('aria-expanded', 'true');
        this.overlay && this.overlay.classList.add('active');
        if (this.searchInput) {
            setTimeout(() => {
                this.searchInput.focus();
                this._positionSuggestPanel();
            }, 50);
        }
    }

    _positionSuggestPanel() {
        if (!this.searchBar) return;
        const panel = this.searchBar.querySelector('.search-suggest-panel');
        if (!panel) return;
        const rect = this.searchBar.getBoundingClientRect();
        panel.style.top = rect.bottom + 'px';
    }

    closeSearch() {
        this.isSearchOpen = false;
        this.searchBar && this.searchBar.classList.remove('active');
        this.searchBtn && this.searchBtn.setAttribute('aria-expanded', 'false');
        if (!this.isDrawerOpen) {
            this.overlay && this.overlay.classList.remove('active');
        }
    }

    toggleCategories() {
        if (!this.categoriesSub || !this.shopToggle) return;
        const isOpen = this.categoriesSub.classList.contains('open');
        this.categoriesSub.classList.toggle('open', !isOpen);
        this.shopToggle.classList.toggle('expanded', !isOpen);
        this.shopToggle.setAttribute('aria-expanded', String(!isOpen));
    }

    async loadCategories() {
        if (this.categoriesLoaded || !this.categoriesSub) return;
        try {
            const res = await fetch((window.BASE_URL || '') + '/api/categories.php');
            const cats = await res.json();
            if (cats.length > 0) {
                const viewAll = this.categoriesSub.querySelector('a');
                cats.forEach(cat => {
                    const a = document.createElement('a');
                    a.href = (window.BASE_URL || '') + '/shop.php?category=' + cat.id;
                    a.textContent = cat.name;
                    a.addEventListener('click', () => this.closeDrawer());
                    this.categoriesSub.insertBefore(a, viewAll);
                });
            }
        } catch (e) {}
        this.categoriesLoaded = true;
    }
}

/**
 * Hamburger Menu Controller
 */
class HamburgerMenu {
    constructor() {
        this.toggle = document.querySelector('.hamburger-toggle');
        this.panel = document.querySelector('.hamburger-menu-panel');
        this.isOpen = false;
        this.categoriesLoaded = false;
        this.init();
    }

    init() {
        if (!this.toggle || !this.panel) return;
        if (window.innerWidth > 768) {
            this.toggle.parentElement.addEventListener('mouseenter', this.handleMouseEnter.bind(this));
            this.toggle.parentElement.addEventListener('mouseleave', this.handleMouseLeave.bind(this));
            this.toggle.addEventListener('mouseenter', this.loadCategories.bind(this), { once: true });
        }
        this.toggle.addEventListener('click', this.handleToggle.bind(this));
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));
        this.toggle.addEventListener('click', this.loadCategories.bind(this), { once: true });
    }

    handleMouseEnter() {
        if (!this.isOpen) {
            this.loadCategories();
            this.open(false);
        }
    }

    handleMouseLeave() {
        setTimeout(() => {
            if (!this.panel.matches(':hover') && !this.toggle.matches(':hover')) {
                this.close();
            }
        }, 100);
    }

    handleToggle(event) {
        event.preventDefault();
        event.stopPropagation();
        if (this.isOpen) {
            this.close();
        } else {
            const viaKeyboard = event.detail === 0;
            this.open(viaKeyboard);
        }
    }

    open(viaKeyboard = false) {
        this.isOpen = true;
        this.panel.classList.add('show');
        this.toggle.setAttribute('aria-expanded', 'true');
        if (viaKeyboard) {
            const firstFocusable = this.panel.querySelector('a');
            if (firstFocusable) setTimeout(() => firstFocusable.focus(), 100);
        }
    }

    close() {
        this.isOpen = false;
        this.panel.classList.remove('show');
        this.toggle.setAttribute('aria-expanded', 'false');
    }

    handleOutsideClick(event) {
        if (window.innerWidth <= 768) return;
        if (this.isOpen && !this.panel.contains(event.target) && !this.toggle.contains(event.target)) {
            this.close();
        }
    }

    handleKeydown(event) {
        if (!this.isOpen) return;
        if (event.key === 'Escape') this.close();
        if (event.key === 'Tab') this.trapFocus(event);
    }

    trapFocus(event) {
        const focusable = this.panel.querySelectorAll('a, button, [tabindex]:not([tabindex="-1"])');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey) {
            if (document.activeElement === first) {
                event.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }

    async loadCategories() {
        if (this.categoriesLoaded) return;
        try {
            const response = await fetch(`${window.BASE_URL || ''}/api/categories.php`);
            if (response.ok) {
                const categories = await response.json();
                if (categories.length > 0) this.renderCategories(categories);
            }
        } catch (error) {}
        this.categoriesLoaded = true;
    }

    renderCategories(categories) {
        const existing = this.panel.querySelector('.categories-grid');
        if (existing) existing.remove();
        const grid = document.createElement('div');
        grid.className = 'categories-grid';
        categories.forEach(category => {
            const link = document.createElement('a');
            link.href = `${window.BASE_URL || ''}/shop.php?category=${category.id}`;
            link.className = 'category-item';
            link.textContent = category.name;
            link.setAttribute('role', 'menuitem');
            link.addEventListener('click', () => this.close());
            grid.appendChild(link);
        });
        const footer = this.panel.querySelector('.hamburger-menu-footer');
        if (footer) this.panel.insertBefore(grid, footer);
        else this.panel.appendChild(grid);
    }
}

/**
 * Profile Dropdown Controller
 */
class ProfileDropdown {
    constructor() {
        this.toggle = document.querySelector('.profile-toggle-clean');
        this.menu = document.querySelector('.profile-dropdown .dropdown-menu');
        this.isOpen = false;
        this.init();
    }
    
    init() {
        if (!this.toggle || !this.menu) return;
        if (window.innerWidth > 768) {
            this.toggle.parentElement.addEventListener('mouseenter', this.handleMouseEnter.bind(this));
            this.toggle.parentElement.addEventListener('mouseleave', this.handleMouseLeave.bind(this));
        }
        this.toggle.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) return; 
            this.handleToggle(e);
        });
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }
    
    handleMouseEnter() {
        if (!this.isOpen) this.open(false);
    }
    
    handleMouseLeave() {
        setTimeout(() => {
            if (!this.menu.matches(':hover') && !this.toggle.matches(':hover')) this.close();
        }, 100);
    }
    
    handleToggle(event) {
        event.preventDefault();
        event.stopPropagation();
        if (this.isOpen) this.close();
        else {
            const viaKeyboard = event.detail === 0;
            this.open(viaKeyboard);
        }
    }
    
    open(viaKeyboard = false) {
        this.isOpen = true;
        this.menu.style.display = 'block';
        this.toggle.setAttribute('aria-expanded', 'true');
        if (viaKeyboard) {
            const firstItem = this.menu.querySelector('a, button');
            if (firstItem) setTimeout(() => firstItem.focus(), 50);
        }
    }
    
    close() {
        this.isOpen = false;
        this.menu.style.display = '';
        this.toggle.setAttribute('aria-expanded', 'false');
    }
    
    handleOutsideClick(event) {
        if (this.isOpen && !this.menu.contains(event.target) && !this.toggle.contains(event.target)) this.close();
    }
    
    handleKeydown(event) {
        if (!this.isOpen) return;
        if (event.key === 'Escape') this.close();
    }
}

/**
 * Global Utilities
 */
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const existingPills = container.querySelectorAll('.toast-pill');
    existingPills.forEach(p => p.remove());

    const toast = document.createElement('div');
    toast.className = 'toast-pill ' + type;
    toast.innerHTML = '<span>' + message + '</span>';
    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 450);
        }
    }, 3000);
}

function updateHeaderCartBadge(count) {
    const badges = document.querySelectorAll('.nav-cart-badge, .mob-cart-badge');
    badges.forEach(b => {
        b.textContent = count;
        b.style.display = (parseInt(count, 10) > 0) ? 'flex' : 'none';
        b.animate([
            { transform: 'scale(1)' },
            { transform: 'scale(1.4)' },
            { transform: 'scale(1)' }
        ], { duration: 400, easing: 'ease-out' });
    });
}

function initWishlistToggles() {
    function renderWishlistState(btn, saved) {
        btn.classList.toggle('saved', saved);
        btn.setAttribute('aria-label', saved ? 'Remove from wishlist' : 'Add to wishlist');
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        
        const iconHtml = saved
            ? '<svg class="product-wishlist-icon-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20.8 4.6a5.5 5.5 0 00-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 00-7.8 7.8l1.1 1 7.8 7.8 7.8-7.7 1-1.1a5.5 5.5 0 000-7.8z"></path></svg><span class="visually-hidden">In Wishlist</span>'
            : '<svg class="product-wishlist-icon-svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M20.8 4.6a5.5 5.5 0 00-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 00-7.8 7.8l1.1 1 7.8 7.8 7.8-7.7 1-1.1a5.5 5.5 0 000-7.8z"></path></svg><span class="visually-hidden">Add to Wishlist</span>';
        
        btn.innerHTML = iconHtml;
    }

    document.querySelectorAll('.wishlist-toggle-btn[data-product-id]').forEach(btn => {
        btn.addEventListener('click', async function () {
            const productId = this.dataset.productId;
            if (!productId) return;

            const isGuest = this.dataset.guest === "true";

            if (isGuest) {
                let wishlist = JSON.parse(localStorage.getItem('ll_temporary_wishlist') || '[]');
                let saved = false;
                const strId = String(productId);
                if (wishlist.includes(strId)) {
                    wishlist = wishlist.filter(id => id !== strId);
                } else {
                    wishlist.push(strId);
                    saved = true;
                }
                localStorage.setItem('ll_temporary_wishlist', JSON.stringify(wishlist));
                document.cookie = 'll_guest_wishlist=' + wishlist.join(',') + ';path=/;max-age=' + (60 * 60 * 24 * 30);
                renderWishlistState(this, saved);
                showToast(saved ? 'Added to wishlist!' : 'Removed from wishlist');

                if (!saved && window.location.pathname.indexOf('/account/wishlist.php') !== -1) {
                    const card = this.closest('.product-card');
                    if (card) {
                        card.style.transition = 'opacity 0.4s, transform 0.4s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            const grid = document.querySelector('.wishlist-grid');
                            if (grid && !grid.querySelector('.product-card')) {
                                window.location.reload();
                            }
                        }, 450);
                    }
                }
                return;
            }

            const csrf = this.dataset.csrf;
            if (!csrf) return;
            this.classList.add('loading');

            try {
                const body = new URLSearchParams({ product_id: productId, csrf });
                const res = await fetch((window.BASE_URL || '') + '/wishlist-toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body,
                });
                if (res.status === 401) {
                    window.location.href = (window.BASE_URL || '') + '/login.php?redirect=' + encodeURIComponent(window.location.pathname);
                    return;
                }
                const data = await res.json();
                const saved = data.action === 'added';
                renderWishlistState(this, saved);
                showToast(saved ? 'Added to wishlist!' : 'Removed from wishlist');

                if (!saved && window.location.pathname.indexOf('/account/wishlist.php') !== -1) {
                    const card = this.closest('.product-card');
                    if (card) {
                        card.style.transition = 'opacity 0.4s, transform 0.4s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            card.remove();
                            const grid = document.querySelector('.wishlist-grid');
                            if (grid && !grid.querySelector('.product-card')) {
                                window.location.reload();
                            }
                        }, 450);
                    }
                }
            } catch (e) {
                console.warn('Wishlist toggle failed:', e);
            } finally {
                this.classList.remove('loading');
            }
        });
    });
}

function initQuickShare() {
    document.addEventListener("click", async function(e) {
        const btn = e.target.closest('.product-share-trigger, .product-mobile-share-trigger, .product-card-quick-share');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const shareData = {
            title: btn.dataset.shareTitle || 'Check this out!',
            text: 'Check out this product on London Labels',
            url: btn.dataset.shareUrl || window.location.href
        };

        // Native share sheet (iOS/Android) — requires HTTPS
        if (navigator.share) {
            try {
                await navigator.share(shareData);
            } catch (err) {
                // AbortError = user dismissed the share sheet intentionally — do nothing
                if (err && err.name === 'AbortError') return;
                // Any other error (e.g. DataError) — fall through to clipboard
            }
            return; // native share was attempted — don't also copy to clipboard
        }

        // Clipboard fallback for desktop / non-HTTPS
        const url = shareData.url;
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(url);
                showToast('Link copied to clipboard!');
            } else {
                throw new Error('Clipboard API unavailable');
            }
        } catch (err) {
            // Legacy textarea fallback
            try {
                const textArea = document.createElement("textarea");
                textArea.value = url;
                textArea.style.cssText = "position:fixed;left:-9999px;top:0;opacity:0;";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (ok) {
                    showToast('Link copied to clipboard!');
                } else {
                    showToast('Could not copy link', 'error');
                }
            } catch (fallbackErr) {
                showToast('Could not copy link', 'error');
            }
        }
    });
}

function initAddToCartAjax() {
    document.addEventListener('submit', async function(e) {
        const form = e.target;
        if (form.id !== 'add-to-cart-form') return;
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        if (btn) btn.classList.add('btn-loading');

        try {
            const formData = new FormData(form);
            formData.append('ajax_add', '1');
            const res = await fetch((window.BASE_URL || '') + '/cart.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.ok) {
                showToast(data.message || 'Added to cart!');
                updateHeaderCartBadge(data.count);
            } else {
                showToast(data.error || 'Check stock and try again', 'error');
            }
        } catch (err) {
            form.submit();
        } finally {
            if (btn) btn.classList.remove('btn-loading');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    window.mobileNav = new MobileNav();
    window.hamburgerMenu = new HamburgerMenu();
    window.profileDropdown = new ProfileDropdown();
    initWishlistToggles();
    initQuickShare();
    initAddToCartAjax();
});
