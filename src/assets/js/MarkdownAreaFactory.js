_context.invoke('App', function(Url) {

    var MarkdownAreaFactory = _context.extend(function(ajax) {
        this._ = {
            ajax: ajax
        };
    }, {
        create(elem, url, param) {
            url = Url.from(url);

            return new MarkdownArea(elem, {
                extensions: [
                    new MarkdownAreaSuggest(this._loadSuggestions.bind(this, url, param), {
                        pattern: /@[a-z0-9._]*/i
                    })
                ]
            });
        },

        _loadSuggestions(url, param, prefix, signal) {
            url.setParam(param, prefix.substring(1));

            var request = this._.ajax.get(url.toAbsolute());
            signal.addEventListener('abort', request.abort.bind(request));

            return request.then(function(response) {
                return response.getPayload().map(function(uid) { return '@' + uid; });
            });
        }
    });

    _context.register(MarkdownAreaFactory, 'MarkdownAreaFactory');

}, {
    Url: 'Utils.Url'
});
