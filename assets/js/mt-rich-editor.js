/**
 * Mobility Trailblazers Rich Text Editor
 * 
 * Lightweight, bulletproof rich text editing with graceful fallback
 * 
 * @package MobilityTrailblazers
 * @since 2.5.32
 */
(function(window, document) {
    'use strict';
    // Rich Text Editor Module
    window.MTRichEditor = {
        // Configuration
        config: {
            maxHistorySize: 50,
            autosaveInterval: 30000, // 30 seconds
            supportedTags: ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'a', 'blockquote'],
            toolbarButtons: []
        },
        // Get localized toolbar buttons
        getToolbarButtons: function() {
            var i18n = window.mt_editor_i18n || {};
            var toolbar = i18n.toolbar || {};
            return [
                { command: 'bold', icon: 'dashicons-editor-bold', title: toolbar.bold || 'Bold (Ctrl+B)', shortcut: 'Ctrl+B' },
                { command: 'italic', icon: 'dashicons-editor-italic', title: toolbar.italic || 'Italic (Ctrl+I)', shortcut: 'Ctrl+I' },
                { command: 'underline', icon: 'dashicons-editor-underline', title: toolbar.underline || 'Underline (Ctrl+U)', shortcut: 'Ctrl+U' },
                { type: 'separator' },
                { command: 'heading', icon: 'dashicons-heading', title: toolbar.headings || 'Headings', type: 'dropdown' },
                { type: 'separator' },
                { command: 'insertUnorderedList', icon: 'dashicons-editor-ul', title: toolbar.unordered_list || 'Bullet List' },
                { command: 'insertOrderedList', icon: 'dashicons-editor-ol', title: toolbar.ordered_list || 'Numbered List' },
                { type: 'separator' },
                { command: 'createLink', icon: 'dashicons-admin-links', title: toolbar.insert_link || 'Insert Link (Ctrl+K)', shortcut: 'Ctrl+K' },
                { command: 'unlink', icon: 'dashicons-editor-unlink', title: toolbar.remove_link || 'Remove Link' },
                { type: 'separator' },
                { command: 'removeFormat', icon: 'dashicons-editor-removeformatting', title: toolbar.remove_format || 'Clear Formatting' },
                { type: 'separator' },
                { command: 'undo', icon: 'dashicons-undo', title: toolbar.undo || 'Undo (Ctrl+Z)', shortcut: 'Ctrl+Z' },
                { command: 'redo', icon: 'dashicons-redo', title: toolbar.redo || 'Redo (Ctrl+Y)', shortcut: 'Ctrl+Y' }
            ]
        },
        // Editor instances
        editors: {},
        // History management
        history: {},
        historyIndex: {},
        // Feature detection
        isSupported: function() {
            return 'contentEditable' in document.body && 
                   typeof document.execCommand === 'function';
        },
        // Initialize editor
        init: function(containerId, options) {
            options = options || {};
            // Check browser support
            if (!this.isSupported()) {
                return this.initFallback(containerId, options);
            }
            const container = document.getElementById(containerId);
            if (!container) {
                // Error logging removed for production
                return null;
            }
            // Create editor structure
            const editorWrapper = this.createEditorStructure(containerId, options);
            container.innerHTML = '';
            container.appendChild(editorWrapper);
            // Initialize editor instance
            const editor = editorWrapper.querySelector('.mt-rich-editor-content');
            this.editors[containerId] = editor;
            // Initialize history
            this.history[containerId] = [];
            this.historyIndex[containerId] = -1;
            // Set initial content
            if (options.content) {
                editor.innerHTML = this.sanitizeHTML(options.content);
                this.saveHistory(containerId);
            }
            // Bind events
            this.bindEditorEvents(containerId, editor);
            this.bindToolbarEvents(containerId, editorWrapper);
            // Start autosave if enabled
            if (options.autosave && options.onAutosave) {
                this.startAutosave(containerId, options.onAutosave);
            }
            return editor;
        },
        // Create editor HTML structure
        createEditorStructure: function(containerId, options) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mt-rich-editor-wrapper';
            wrapper.setAttribute('data-editor-id', containerId);
            // Create toolbar
            const toolbar = this.createToolbar(containerId);
            wrapper.appendChild(toolbar);
            // Create content area
            const content = document.createElement('div');
            content.className = 'mt-rich-editor-content';
            content.contentEditable = true;
            content.setAttribute('role', 'textbox');
            content.setAttribute('aria-multiline', 'true');
            content.setAttribute('aria-label', options.label || 'Rich text editor');
            // Set min/max height
            if (options.minHeight) content.style.minHeight = options.minHeight + 'px';
            if (options.maxHeight) content.style.maxHeight = options.maxHeight + 'px';
            wrapper.appendChild(content);
            // Add status bar
            const statusBar = document.createElement('div');
            statusBar.className = 'mt-rich-editor-status';
            statusBar.innerHTML = '<span class="mt-char-count">0 characters</span>';
            wrapper.appendChild(statusBar);
            return wrapper;
        },
        // Create toolbar
        createToolbar: function(containerId) {
            const toolbar = document.createElement('div');
            toolbar.className = 'mt-rich-editor-toolbar';
            toolbar.setAttribute('role', 'toolbar');
            // Get localized buttons
            const toolbarButtons = this.getToolbarButtons();
            toolbarButtons.forEach(button => {
                if (button.type === 'separator') {
                    const separator = document.createElement('span');
                    separator.className = 'mt-toolbar-separator';
                    toolbar.appendChild(separator);
                } else if (button.type === 'dropdown') {
                    const dropdown = this.createDropdown(button);
                    toolbar.appendChild(dropdown);
                } else {
                    const btn = this.createToolbarButton(button);
                    toolbar.appendChild(btn);
                }
            });
            return toolbar;
        },
        // Create toolbar button
        createToolbarButton: function(config) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mt-toolbar-button';
            button.setAttribute('data-command', config.command);
            button.setAttribute('title', config.title);
            button.setAttribute('aria-label', config.title);
            const icon = document.createElement('span');
            icon.className = 'dashicons ' + config.icon;
            button.appendChild(icon);
            return button;
        },
        // Create dropdown
        createDropdown: function(config) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mt-toolbar-dropdown';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'mt-toolbar-button mt-dropdown-toggle';
            button.setAttribute('data-command', config.command);
            button.setAttribute('title', config.title);
            const icon = document.createElement('span');
            icon.className = 'dashicons ' + config.icon;
            button.appendChild(icon);
            const dropdown = document.createElement('div');
            dropdown.className = 'mt-dropdown-menu';
            var i18n = window.mt_editor_i18n || {};
            var dropdown_text = i18n.dropdown || {};
            dropdown.innerHTML = `
                <button type="button" data-heading="p">${dropdown_text.normal_text || 'Normal Text'}</button>
                <button type="button" data-heading="h1">${dropdown_text.heading_1 || 'Heading 1'}</button>
                <button type="button" data-heading="h2">${dropdown_text.heading_2 || 'Heading 2'}</button>
                <button type="button" data-heading="h3">${dropdown_text.heading_3 || 'Heading 3'}</button>
            `;
            wrapper.appendChild(button);
            wrapper.appendChild(dropdown);
            return wrapper;
        },
        // Bind editor events
        bindEditorEvents: function(containerId, editor) {
            const self = this;
            // Content change tracking
            editor.addEventListener('input', function() {
                self.updateCharCount(containerId);
                self.saveHistory(containerId);
            });
            // Keyboard shortcuts
            editor.addEventListener('keydown', function(e) {
                self.handleKeyboardShortcuts(e, containerId);
            });
            // Paste handling
            editor.addEventListener('paste', function(e) {
                self.handlePaste(e, containerId);
            });
            // Focus/blur
            editor.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            editor.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        },
        // Bind toolbar events
        bindToolbarEvents: function(containerId, wrapper) {
            const self = this;
            const toolbar = wrapper.querySelector('.mt-rich-editor-toolbar');
            // Regular buttons
            toolbar.querySelectorAll('.mt-toolbar-button:not(.mt-dropdown-toggle)').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const command = this.getAttribute('data-command');
                    self.executeCommand(containerId, command);
                });
            });
            // Dropdown handling
            toolbar.querySelectorAll('.mt-dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropdown = this.nextElementSibling;
                    dropdown.classList.toggle('show');
                });
            });
            // Dropdown items
            toolbar.querySelectorAll('.mt-dropdown-menu button').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const heading = this.getAttribute('data-heading');
                    self.formatHeading(containerId, heading);
                    this.parentElement.classList.remove('show');
                });
            });
            // Close dropdowns on outside click
            document.addEventListener('click', function() {
                toolbar.querySelectorAll('.mt-dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            });
        },
        // Execute formatting command
        executeCommand: function(containerId, command, value) {
            const editor = this.editors[containerId];
            if (!editor) return;
            editor.focus();
            switch(command) {
                case 'createLink':
                    this.insertLink(containerId);
                    break;
                case 'undo':
                    this.undo(containerId);
                    break;
                case 'redo':
                    this.redo(containerId);
                    break;
                case 'removeFormat':
                    document.execCommand('removeFormat', false, null);
                    document.execCommand('formatBlock', false, 'p');
                    break;
                default:
                    document.execCommand(command, false, value || null);
            }
            this.saveHistory(containerId);
            this.updateToolbarState(containerId);
        },
        // Format heading
        formatHeading: function(containerId, tag) {
            const editor = this.editors[containerId];
            if (!editor) return;
            editor.focus();
            document.execCommand('formatBlock', false, tag);
            this.saveHistory(containerId);
            this.updateToolbarState(containerId);
        },
        // Insert link
        insertLink: function(containerId) {
            var i18n = window.mt_editor_i18n || {};
            var prompts = i18n.prompts || {};
            const url = prompt(prompts.enter_url || 'Enter URL:', 'https://');
            if (url && url !== 'https://') {
                document.execCommand('createLink', false, url);
                // Add target="_blank" to new links
                const editor = this.editors[containerId];
                const links = editor.querySelectorAll('a[href="' + url + '"]');
                links.forEach(link => {
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                });
            }
        },
        // Handle keyboard shortcuts
        handleKeyboardShortcuts: function(e, containerId) {
            const ctrl = e.ctrlKey || e.metaKey;
            if (ctrl) {
                switch(e.key.toLowerCase()) {
                    case 'b':
                        e.preventDefault();
                        this.executeCommand(containerId, 'bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        this.executeCommand(containerId, 'italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        this.executeCommand(containerId, 'underline');
                        break;
                    case 'k':
                        e.preventDefault();
                        this.executeCommand(containerId, 'createLink');
                        break;
                    case 'z':
                        if (!e.shiftKey) {
                            e.preventDefault();
                            this.undo(containerId);
                        }
                        break;
                    case 'y':
                        e.preventDefault();
                        this.redo(containerId);
                        break;
                }
            }
        },
        // Handle paste
        handlePaste: function(e, containerId) {
            e.preventDefault();
            let text = '';
            if (e.clipboardData) {
                text = e.clipboardData.getData('text/html') || e.clipboardData.getData('text/plain');
            } else if (window.clipboardData) {
                text = window.clipboardData.getData('Text');
            }
            // Clean and insert
            const cleaned = this.sanitizeHTML(text);
            document.execCommand('insertHTML', false, cleaned);
            this.saveHistory(containerId);
        },
        // Sanitize HTML
        sanitizeHTML: function(html) {
            // Create temporary element
            const temp = document.createElement('div');
            temp.innerHTML = html;
            // Remove script and style tags
            temp.querySelectorAll('script, style, meta, link').forEach(el => el.remove());
            // Remove dangerous attributes
            temp.querySelectorAll('*').forEach(el => {
                // Keep only safe attributes
                const allowedAttrs = ['href', 'target', 'rel', 'title', 'alt'];
                Array.from(el.attributes).forEach(attr => {
                    if (!allowedAttrs.includes(attr.name) && !attr.name.startsWith('data-')) {
                        el.removeAttribute(attr.name);
                    }
                });
                // Remove event handlers
                for (let prop in el) {
                    if (prop.startsWith('on')) {
                        el[prop] = null;
                    }
                }
            });
            // Clean up empty paragraphs
            temp.querySelectorAll('p').forEach(p => {
                if (!p.textContent.trim() && !p.querySelector('br')) {
                    p.remove();
                }
            });
            return temp.innerHTML;
        },
        // History management
        saveHistory: function(containerId) {
            const editor = this.editors[containerId];
            if (!editor) return;
            const content = editor.innerHTML;
            const history = this.history[containerId];
            const index = this.historyIndex[containerId];
            // Remove any history after current index
            history.splice(index + 1);
            // Add new state
            history.push(content);
            // Limit history size
            if (history.length > this.config.maxHistorySize) {
                history.shift();
            }
            this.historyIndex[containerId] = history.length - 1;
        },
        // Undo
        undo: function(containerId) {
            const history = this.history[containerId];
            let index = this.historyIndex[containerId];
            if (index > 0) {
                index--;
                this.historyIndex[containerId] = index;
                this.editors[containerId].innerHTML = history[index];
                this.updateToolbarState(containerId);
            }
        },
        // Redo
        redo: function(containerId) {
            const history = this.history[containerId];
            let index = this.historyIndex[containerId];
            if (index < history.length - 1) {
                index++;
                this.historyIndex[containerId] = index;
                this.editors[containerId].innerHTML = history[index];
                this.updateToolbarState(containerId);
            }
        },
        // Update toolbar button states
        updateToolbarState: function(containerId) {
            const wrapper = document.querySelector('[data-editor-id="' + containerId + '"]');
            if (!wrapper) return;
            const toolbar = wrapper.querySelector('.mt-rich-editor-toolbar');
            // Update button active states
            toolbar.querySelectorAll('.mt-toolbar-button').forEach(button => {
                const command = button.getAttribute('data-command');
                if (command && command !== 'undo' && command !== 'redo') {
                    const isActive = document.queryCommandState(command);
                    button.classList.toggle('active', isActive);
                }
            });
            // Update undo/redo states
            const undoBtn = toolbar.querySelector('[data-command="undo"]');
            const redoBtn = toolbar.querySelector('[data-command="redo"]');
            if (undoBtn) {
                undoBtn.disabled = this.historyIndex[containerId] <= 0;
            }
            if (redoBtn) {
                redoBtn.disabled = this.historyIndex[containerId] >= this.history[containerId].length - 1;
            }
        },
        // Update character count
        updateCharCount: function(containerId) {
            const editor = this.editors[containerId];
            const wrapper = document.querySelector('[data-editor-id="' + containerId + '"]');
            if (!editor || !wrapper) return;
            const charCount = editor.textContent.length;
            const counter = wrapper.querySelector('.mt-char-count');
            if (counter) {
                counter.textContent = charCount + ' characters';
            }
        },
        // Get content
        getContent: function(containerId) {
            const editor = this.editors[containerId];
            return editor ? this.sanitizeHTML(editor.innerHTML) : '';
        },
        // Set content
        setContent: function(containerId, content) {
            const editor = this.editors[containerId];
            if (editor) {
                editor.innerHTML = this.sanitizeHTML(content);
                this.saveHistory(containerId);
                this.updateCharCount(containerId);
            }
        },
        // Start autosave
        startAutosave: function(containerId, callback) {
            const self = this;
            setInterval(function() {
                const content = self.getContent(containerId);
                if (content) {
                    callback(content);
                }
            }, this.config.autosaveInterval);
        },
        // Fallback for unsupported browsers
        initFallback: function(containerId, options) {
            const container = document.getElementById(containerId);
            if (!container) return null;
            const textarea = document.createElement('textarea');
            textarea.className = 'mt-rich-editor-fallback';
            textarea.id = containerId + '-fallback';
            textarea.rows = 10;
            if (options.content) {
                textarea.value = this.stripHTML(options.content);
            }
            container.innerHTML = '';
            container.appendChild(textarea);
            // Add help text
            const help = document.createElement('div');
            help.className = 'mt-editor-help';
            help.innerHTML = 'Use **text** for bold, *text* for italic, and [text](url) for links.';
            container.appendChild(help);
            return textarea;
        },
        // Strip HTML for fallback
        stripHTML: function(html) {
            const temp = document.createElement('div');
            temp.innerHTML = html;
            return temp.textContent || temp.innerText || '';
        },
        // Destroy editor
        destroy: function(containerId) {
            delete this.editors[containerId];
            delete this.history[containerId];
            delete this.historyIndex[containerId];
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = '';
            }
        }
    };
})(window, document);
