_context.invoke('App', function (DOM, Arrays) {

    var ClassSwitcher = _context.extend(function(page) {
        this._ = {
            page: page
        };

        this._handleResponse = this._handleResponse.bind(this);
        this._.page.on('transaction-created', this._handleTransaction.bind(this));
    }, {
        _handleTransaction: function (evt) {
            if (!evt.data.transaction.isBackground()) {
                evt.data.transaction.on('ajax-response', this._handleResponse);
            }
        },

        _handleResponse: function (evt) {
            var elems = Arrays.createFrom(document.querySelectorAll('[data-class-source]'));
            var payload = evt.data.response.getPayload();

            elems.forEach(function (elem) {
                var src = DOM.getData(elem, 'class-source'),
                    target, tmp;

                if (src in payload) {
                    target = DOM.getData(elem, 'class-target');

                    if (!target) {
                        elem.className = payload[src];
                    } else {
                        tmp = elem.querySelector('[data-' + target + '].active');
                        tmp && DOM.toggleClass(tmp, 'active', false);
                        tmp = elem.querySelector('[data-' + target + '="' + payload[src] + '"]');
                        tmp && DOM.toggleClass(tmp, 'active', true);
                    }
                }
            });
        }
    });

    _context.register(ClassSwitcher, 'ClassSwitcher');

}, {
    DOM: 'Utils.DOM',
    Arrays: 'Utils.Arrays'
});
