/**
 * Exit Links Manager - Frontend JavaScript
 * Handles external link detection and redirection
 */

(function() {
    'use strict';

    const config = {
        siteUrl: exitLinksManager.siteUrl,
        leavingUrl: exitLinksManager.leavingUrl,
        selectors: {
            content: '.exit-links-content',
            links: 'a[href]'
        }
    };

    /**
     * Get the domain from a URL
     * @param {string} url - The URL to extract domain from
     * @returns {string} The domain
     */
    function getDomain(url) {
        try {
            const urlObj = new URL(url, config.siteUrl);
            return urlObj.hostname.toLowerCase();
        } catch (e) {
            return '';
        }
    }

    /**
     * Get the current site domain
     * @returns {string} The current site domain
     */
    function getCurrentDomain() {
        return getDomain(config.siteUrl);
    }

    /**
     * Check if a URL is external
     * @param {string} url - The URL to check
     * @returns {boolean} True if external, false otherwise
     */
    function isExternalUrl(url) {
        if (!url || url.startsWith('#') || url.startsWith('/')) {
            return false;
        }

        if (url.includes('/leaving?url=')) {
            return false;
        }

        if (!url.match(/^https?:\/\//i) && !url.startsWith('//')) {
            return false;
        }

        const urlDomain = getDomain(url);
        const currentDomain = getCurrentDomain();

        if (!urlDomain || !currentDomain) {
            return false;
        }

        const cleanUrlDomain = urlDomain.replace(/^www\./, '');
        const cleanCurrentDomain = currentDomain.replace(/^www\./, '');

        return cleanUrlDomain !== cleanCurrentDomain;
    }

    /**
     * Handle link click for external links
     * @param {Event} event - The click event
     */
    function handleLinkClick(event) {
        const link = event.currentTarget;
        const href = link.getAttribute('href');

        if (!isExternalUrl(href)) {
            return;
        }

        event.preventDefault();

        const encodedUrl = encodeURIComponent(href);
        const redirectUrl = `${config.leavingUrl}?url=${encodedUrl}`;

        window.open(redirectUrl, '_blank');

    }

    /**
     * Process links in a container
     * @param {Element} container - The container to process
     */
    function processLinks(container) {
        const links = container.querySelectorAll(config.selectors.links);
        
        links.forEach(link => {
            if (link.hasAttribute('data-exit-links-processed')) {
                return;
            }

            link.setAttribute('data-exit-links-processed', 'true');

            link.addEventListener('click', handleLinkClick);
        });
    }

    /**
     * Initialize the exit links manager
     */
    function init() {
        const contentContainers = document.querySelectorAll(config.selectors.content);
        contentContainers.forEach(processLinks);

        const mainContent = document.querySelector('main, .content, .entry-content, .post-content, #content');
        if (mainContent && contentContainers.length === 0) {
            processLinks(mainContent);
        }

        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.matches && node.matches(config.selectors.content)) {
                                processLinks(node);
                            }
                            
                            const containers = node.querySelectorAll ? 
                                node.querySelectorAll(config.selectors.content) : [];
                            containers.forEach(processLinks);

                            if (node.matches && node.matches('main, .content, .entry-content, .post-content, #content')) {
                                processLinks(node);
                            }
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
