_context.invoke('App', function(Strings) {

    var reValidMention = /(?:^|\s)$/i,
        reMention = /@([a-z0-9._]+)$/i,
        reValidKeys = /^[a-z0-9._]$/i,
        reModifiers = /^(Control|Alt|Shift|Meta|Super)$/;

    var Mention = _context.extend(function (ajax, elem, url, param) {
        this._ = {
            ajax: ajax,
            active: false,
            request: null,
            elem: null,
            candidates: null,
            current: null
        };

        this._handleKey = this._handleKey.bind(this);
        this._candidatesReceived = this._candidatesReceived.bind(this);
        this._handleError = this._handleError.bind(this);
        this.setElement(elem);
        this.setUrl(url, param);
    }, {
        setElement: function (elem) {
            if (this._.elem) {
                this._.elem.removeEventListener('keydown', this._handleKey);
            }

            this._.elem = elem;
            this._.elem.addEventListener('keydown', this._handleKey);
        },

        setUrl: function (url, param) {
            if (typeof url === 'string') {
                this._getUrl = function (query) {
                    return url.replace(/(\?.*?)?(#.*)?$/, function(_, q, h) {
                        return q + (q ? '&' : '?') + encodeURIComponent(param || 'q') + '=' + encodeURIComponent(query) + h;
                    });
                }
            } else if (typeof url === 'function') {
                this._getUrl = url;
            } else {
                throw new TypeError('Invalid argument to setUrl(): must be a string or a function, ' + (typeof url) + ' given');
            }
        },

        destroy: function () {
            this._.active = false;
            this._.elem && this._.elem.removeEventListener('keydown', this._handleKey);
            this._.request && this._.request.abort();
            this._.elem = this._getUrl = this._.candidates = this._.current = null;
        },

        _handleKey: function (evt) {
            if (!this._.active) {
                if (evt.key === '@' && reValidMention.test(this._getPrefix())) {
                    this._.active = true;
                    this._updateCandidates();
                }
            } else if (!reModifiers.test(evt.key)) {
                evt.preventDefault();

                if (evt.key === 'Enter' || evt.key === 'Tab' || evt.key === 'ArrowRight') {
                    this._render(true);
                    this._.active = false;
                    this._.request && this._.request.abort();
                    this._.candidates = this._.current = this._.request = null;
                } else if (evt.key === 'ArrowDown' || evt.key === 'ArrowUp') {
                    if (this._.candidates) {
                        this._.current === null && (this._.current = 0);
                        this._.current = (this._.current + (evt.key === 'ArrowDown' ? 1 : -1) + this._.candidates.length) % this._.candidates.length;
                        this._render();
                    }
                } else if (reValidKeys.test(evt.key)) {
                    this._append(evt.key);
                    this._updateCandidates();
                } else {
                    this._.active = false;
                    this._.request && this._.request.abort();
                    this._.candidates = this._.current = this._.request = null;

                    if (evt.key === ' ') {
                        this._append(' ');
                    } else {
                        this._render();
                    }
                }
            }
        },

        _updateCandidates: function () {
            var mention = reMention.exec(this._getPrefix());

            this._.request && this._.request.abort();

            this._.request = this._.ajax.get(this._getUrl(mention ? mention[1] : null));
            this._.request.then(this._candidatesReceived, this._handleError);
        },

        _candidatesReceived: function (response) {
            if (!this._.active) {
                return;
            }

            this._.candidates = response.getPayload();
            this._resolveBestCandidate();
            this._render();
        },

        _handleError: function (err) {
            if (!this._.active || err && err.type === 'abort') {
                return;
            }

            this._.candidates = this._.current = null;
            this._render();
        },

        _append: function (c) {
            var prefix = this._getPrefix(),
                postfix = this._getPostfix();

            this._.elem.value = prefix + c + postfix;
            this._.elem.selectionStart = this._.elem.selectionEnd = prefix.length + 1;
        },

        _render: function (accept) {
            var prefix = this._getPrefix(),
                postfix = this._getPostfix(),
                mention = reMention.exec(prefix),
                candidate = this._.current !== null ? this._.candidates[this._.current] : '';

            if (candidate) {
                if (accept) {
                    if (mention) {
                        prefix = prefix.substring(0, prefix.length - mention[1].length);
                    }
                } else if (mention) {
                    candidate = candidate.substring(mention[1].length);
                }
            }

            this._.elem.value = prefix + candidate + postfix;
            this._.elem.selectionStart = prefix.length + (accept ? candidate.length : 0);
            this._.elem.selectionEnd = prefix.length + candidate.length;
        },

        _resolveBestCandidate: function () {
            this._.current = null;

            if (!this._.candidates) {
                return;
            }

            var mention = reMention.exec(this._getPrefix());

            if (!mention) {
                this._.current = 0;
                return;
            }

            var pattern = new RegExp('^' + Strings.escapeRegex(mention[1]), 'i'),
                i = 0, n = this._.candidates.length;

            for (; i < n; i++) {
                if (pattern.test(this._.candidates[i])) {
                    this._.current = i;
                    break;
                }
            }
        },

        _getPrefix: function () {
            return this._.elem.value.substring(0, this._.elem.selectionStart);
        },

        _getPostfix: function () {
            return this._.elem.value.substring(this._.elem.selectionEnd);
        }
    });

    _context.register(Mention, 'Mention');

}, {
    Strings: 'Utils.Strings'
});
