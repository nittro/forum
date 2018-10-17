_context.invoke('App', function (DOM, Url, DateTime) {

    var PageWidgets = _context.extend(function (page, snippetManager, scrollAgent, history) {
        this._ = {
            page: page,
            snippetManager: snippetManager,
            scrollAgent: scrollAgent,
            history: history,
            navbar: null,
            header: null,
            threshold: 0,
            viewport: null,
            installed: false,
            fixed: false
        };

        this._handleScroll = this._handleScroll.bind(this);
        this._.page.on('transaction-created', this._handleTransaction.bind(this));
        this._.snippetManager.one('after-update', this._init.bind(this));
        this._.snippetManager.on('before-update', this._handleBeforeUpdate.bind(this));
        this._.snippetManager.on('after-update', this._handleAfterUpdate.bind(this));
    }, {
        _init: function () {
            this._.navbar = DOM.getByClassName('header')[0];

            var $d = $(document);

            $d.on('click', '[data-toggle="class"]', this._toggleClass.bind(this));
            $d.on('click', 'body.sidebar-visible .sidebar a', this._hideSidebar.bind(this));
            $d.on('click', '.header-content', this._scrollToTop.bind(this));
            $d.on('change', '.custom-file-input', this._handleFileInput.bind(this));

            DOM.addListener(window, 'resize', this._handleResize.bind(this));
            this._updateViewport();
            this._scrollToFirstPost();
        },

        _toggleClass: function(evt) {
            evt.preventDefault();

            var target = DOM.getData(evt.currentTarget, 'target'),
                className = DOM.getData(evt.currentTarget, 'class', 'active');

            $(target).toggleClass(className);
        },

        _hideSidebar: function() {
            $(document.body).removeClass('sidebar-visible');
        },

        _scrollToFirstPost: function() {
            var url = Url.fromCurrent(),
                offset = this._getFirstPostOffset(url);

            if (offset !== null) {
                url.setParam('r', null);

                this._.history.replace(url.toAbsolute(), document.title, {
                    scrollAgent: {
                        target: offset
                    }
                });
            }
        },

        _getFirstPostOffset: function(url) {
            if (url.hasParam('r')) {
                var elem = DOM.getById('r' + url.getParam('r'));
                return elem ? window.pageYOffset + elem.getBoundingClientRect().top : null;
            } else {
                return null;
            }
        },

        _scrollToTop: function() {
            this._.scrollAgent._scrollTo(0)
        },

        _updateViewport: function() {
            this._.viewport = window.innerWidth;
        },

        _updateThreshold: function () {
            var rect = this._.header.getBoundingClientRect();

            if (this._.fixed && this._.header.scrollWidth > rect.width + 5) {
                this._doToggle(false);
                rect = this._.header.getBoundingClientRect();
            }

            if (rect.height > 40) {
                this._.threshold = rect.height - 40;
            } else {
                this._.threshold = 0;
            }
        },

        _updateScrollHandler: function () {
            if ((this._.threshold <= 0 || this._.viewport >= 768) && this._.installed) {
                DOM.removeListener(window, 'scroll', this._handleScroll);
                this._.installed = false;
            } else if (this._.threshold > 0 && this._.viewport < 768 && !this._.installed) {
                DOM.addListener(window, 'scroll', this._handleScroll);
                this._.installed = true;
            }
        },

        _toggle: function () {
            if (this._.viewport >= 768) {
                this._.fixed && this._doToggle(false);
                return;
            }

            var y = window.pageYOffset;

            if (!this._.fixed && y >= this._.threshold || this._.fixed && y < this._.threshold) {
                this._doToggle(!this._.fixed);
            }
        },

        _doToggle: function (on) {
            this._.fixed = on;
            DOM.toggleClass(this._.navbar, 'fixed-top', on);
            this._.navbar.nextElementSibling.style.marginTop = on ? this._.threshold + 40 + 'px' : 0;
        },

        _updateTimes: function () {
            var now = DateTime.now(),
                today = now.format('Y-m-d'),
                yday = now.modifyClone('-1 day').format('Y-m-d'),
                y = now.format('Y'),
                elems = document.getElementsByTagName('time'),
                i, n, d, m, dt, t;

            for (i = 0, n = elems.length; i < n; i++) {
                if (!elems.item(i).hasAttribute('data-local')) {
                    elems.item(i).setAttribute('data-local', 'true');
                    d = DateTime.from(elems.item(i).getAttribute('datetime'));
                    m = Math.floor((now - d) / 60);

                    if (m < 5) {
                        t = 'a minute ago';
                    } else if (m < 60) {
                        t = m + ' minutes ago';
                    } else if (m <= 180) {
                        t = (m / 60).toFixed(0) + ' hours ago';
                    } else {
                        dt = d.format('Y-m-d');

                        if (dt === today) {
                            t = 'today at ' + d.format('H:i');
                        } else if (dt === yday) {
                            t = 'yesterday at ' + d.format('H:i');
                        } else if (d.format('Y') === y) {
                            t = d.format('\\o\\n j. n. \\a\\t H:i');
                        } else {
                            t = d.format('\\o\\n j. n. Y \\a\\t H:i');
                        }
                    }

                    elems.item(i).textContent = t;
                }
            }
        },

        _handleTransaction: function(evt) {
            var data = { redraw: null };

            if ('redraw' in evt.data.context) {
                data.redraw = evt.data.context.redraw;
            } else if (evt.data.context.element && evt.data.context.element.hasAttribute('data-redraw')) {
                data.redraw = DOM.getData(evt.data.context.element, 'redraw');
            }

            evt.data.transaction.on('ajax-request', this._handleRequest.bind(this, data));
            evt.data.transaction.on('ajax-response', this._handleResponse.bind(this, data));
        },

        _handleRequest: function (data, evt) {
            if (data.redraw === null && evt.target.isFromHistory()) {
                data.redraw = 'full';
            }

            evt.data.request.setHeader('X-Redraw', data.redraw);
        },

        _handleResponse: function (data, evt) {
            var payload = evt.data.response.getPayload();

            if ('redraw' in payload) {
                data.redraw = payload.redraw;
            }
        },

        _handleBeforeUpdate: function() {
            this._.header = null;
        },

        _handleAfterUpdate: function() {
            this._.header = DOM.getByClassName('header-content')[0];
            this._updateThreshold();
            this._updateScrollHandler();
            this._toggle();
            this._updateTimes();
        },

        _handleScroll: function (evt) {
            if (this._.header && (evt.target === document.body || evt.target === document || evt.target === window)) {
                this._toggle();
            }
        },

        _handleResize: function() {
            if (this._.header) {
                this._updateViewport();
                this._updateThreshold();
                this._updateScrollHandler();
                this._toggle();
            }
        },

        _handleFileInput: function (evt) {
            var files = evt.currentTarget.files;

            $(evt.currentTarget)
                .closest('.custom-file')
                .find('.custom-file-label')
                .text(files.length ? (files.length > 1 ? files.length + ' files' : files.item(0).name) : 'Choose file...');
        }
    });

    _context.register(PageWidgets, 'PageWidgets');

}, {
    DOM: 'Utils.DOM',
    Url: 'Utils.Url',
    DateTime: 'Utils.DateTime'
});
