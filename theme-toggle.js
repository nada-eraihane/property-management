// theme-toggle.js - Universal Dark/Light Mode Toggle System

(function() {
    'use strict';

    // Theme Manager Class
    class ThemeManager {
        constructor() {
            this.currentTheme = 'light'; // Default theme stored in memory
            this.initialized = false;
            this.callbacks = [];
            
            // Auto-initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
            } else {
                this.init();
            }
        }

        init() {
            if (this.initialized) return;
            
            // Set initial theme
            this.setTheme(this.currentTheme);
            
            // Auto-bind any existing toggle buttons
            this.bindToggleButtons();
            
            // Listen for new toggle buttons added dynamically
            this.observeDOM();
            
            this.initialized = true;
            
            // Dispatch initialization event
            this.dispatchEvent('themeManagerReady', { theme: this.currentTheme });
        }

        bindToggleButtons() {
            const toggleButtons = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
            toggleButtons.forEach(button => {
                if (!button.hasAttribute('data-theme-bound')) {
                    button.addEventListener('click', () => this.toggleTheme());
                    button.setAttribute('data-theme-bound', 'true');
                    this.updateToggleButton(button);
                }
            });
        }

        observeDOM() {
            // Watch for dynamically added toggle buttons
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const toggles = node.querySelectorAll ? 
                                node.querySelectorAll('.theme-toggle, [data-theme-toggle]') : [];
                            if (node.classList && (node.classList.contains('theme-toggle') || node.hasAttribute('data-theme-toggle'))) {
                                this.bindSingleToggleButton(node);
                            }
                            toggles.forEach(toggle => this.bindSingleToggleButton(toggle));
                        }
                    });
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        bindSingleToggleButton(button) {
            if (!button.hasAttribute('data-theme-bound')) {
                button.addEventListener('click', () => this.toggleTheme());
                button.setAttribute('data-theme-bound', 'true');
                this.updateToggleButton(button);
            }
        }

        toggleTheme() {
            const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
            this.setTheme(newTheme);
        }

        setTheme(theme) {
            if (theme !== 'light' && theme !== 'dark') {
                console.warn('Invalid theme:', theme, '. Using light theme.');
                theme = 'light';
            }

            this.currentTheme = theme;
            
            // Apply theme to document
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
            } else {
                document.documentElement.removeAttribute('data-theme');
                document.body.classList.add('light-theme');
                document.body.classList.remove('dark-theme');
            }

            // Update all toggle buttons
            this.updateAllToggleButtons();
            
            // Update theme indicators
            this.updateThemeIndicators();
            
            // Execute callbacks
            this.callbacks.forEach(callback => {
                try {
                    callback(theme);
                } catch (e) {
                    console.error('Theme callback error:', e);
                }
            });
            
            // Dispatch theme change event
            this.dispatchEvent('themeChanged', { theme: this.currentTheme });
        }

        updateAllToggleButtons() {
            const toggleButtons = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
            toggleButtons.forEach(button => this.updateToggleButton(button));
        }

        updateToggleButton(button) {
            const iconElement = button.querySelector('[data-theme-icon]') || 
                               button.querySelector('.theme-icon') ||
                               button.querySelector('span:first-child');
            const textElement = button.querySelector('[data-theme-text]') || 
                               button.querySelector('.theme-text') ||
                               button.querySelector('span:last-child');

            if (this.currentTheme === 'dark') {
                if (iconElement) iconElement.textContent = '☀️';
                if (textElement) textElement.textContent = textElement.dataset.lightText || 'Light Mode';
                button.setAttribute('aria-label', 'Switch to light mode');
                button.title = 'Switch to light mode';
            } else {
                if (iconElement) iconElement.textContent = '🌙';
                if (textElement) textElement.textContent = textElement.dataset.darkText || 'Dark Mode';
                button.setAttribute('aria-label', 'Switch to dark mode');
                button.title = 'Switch to dark mode';
            }
        }

        updateThemeIndicators() {
            const indicators = document.querySelectorAll('.theme-indicator, [data-theme-indicator]');
            indicators.forEach(indicator => {
                if (this.currentTheme === 'dark') {
                    indicator.textContent = indicator.dataset.darkText || '🌙 Dark Mode';
                } else {
                    indicator.textContent = indicator.dataset.lightText || '🌞 Light Mode';
                }
            });
        }

        dispatchEvent(eventName, detail) {
            const event = new CustomEvent(eventName, {
                detail: detail,
                bubbles: true,
                cancelable: true
            });
            window.dispatchEvent(event);
        }

        // Public API methods
        getCurrentTheme() {
            return this.currentTheme;
        }

        isDark() {
            return this.currentTheme === 'dark';
        }

        isLight() {
            return this.currentTheme === 'light';
        }

        forceTheme(theme) {
            this.setTheme(theme);
        }

        onThemeChange(callback) {
            if (typeof callback === 'function') {
                this.callbacks.push(callback);
                // Call immediately with current theme
                callback(this.currentTheme);
            }
        }

        offThemeChange(callback) {
            const index = this.callbacks.indexOf(callback);
            if (index > -1) {
                this.callbacks.splice(index, 1);
            }
        }

        // Utility method to get CSS variable value
        getCSSVariable(variableName) {
            return getComputedStyle(document.documentElement)
                .getPropertyValue(variableName).trim();
        }

        // Method to dynamically change theme colors
        setThemeColor(variable, lightValue, darkValue) {
            const root = document.documentElement;
            root.style.setProperty(`--${variable}`, this.currentTheme === 'light' ? lightValue : darkValue);
        }
    }

    // Create global instance
    window.ThemeManager = window.ThemeManager || new ThemeManager();

    // Global convenience functions
    window.toggleTheme = function() {
        window.ThemeManager.toggleTheme();
    };

    window.setTheme = function(theme) {
        window.ThemeManager.forceTheme(theme);
    };

    window.getCurrentTheme = function() {
        return window.ThemeManager.getCurrentTheme();
    };

    window.isDarkMode = function() {
        return window.ThemeManager.isDark();
    };

    window.isLightMode = function() {
        return window.ThemeManager.isLight();
    };

    window.onThemeChange = function(callback) {
        window.ThemeManager.onThemeChange(callback);
    };

    // Auto-detect system preference (optional)
    window.detectSystemTheme = function() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    };

    window.followSystemTheme = function() {
        const systemTheme = window.detectSystemTheme();
        window.ThemeManager.setTheme(systemTheme);
        
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                window.ThemeManager.setTheme(e.matches ? 'dark' : 'light');
            });
        }
    };

})();