_context.invoke('App', function () {

    var PaginatorHelper = _context.extend('Nittro.Object', function() {
        PaginatorHelper.Super.call(this);
        this._forwardEvent = this._forwardEvent.bind(this);
    }, {
        register: function (paginator) {
            paginator.on('page-prepared page-rendered', this._forwardEvent);
        },

        _forwardEvent: function (evt) {
            this.trigger(evt.type, {
                paginator: evt.target
            });
        }
    });

    _context.register(PaginatorHelper, 'PaginatorHelper');

});
