_context.invoke('App.Forms', function (DOM) {

    var BootstrapErrorRenderer = _context.extend('Nittro.Forms.DefaultErrorRenderer', function () {
        BootstrapErrorRenderer.Super.call(this);
    }, {
        addError: function (form, element, message) {
            BootstrapErrorRenderer.Super.prototype.addError.call(this, form, element, message);
            element && DOM.toggleClass(element, 'is-invalid', true);
        },

        cleanupErrors: function (form, element) {
            BootstrapErrorRenderer.Super.prototype.cleanupErrors.call(this, form, element);
            DOM.toggleClass(element || DOM.getByClassName('is-invalid', form), 'is-invalid', false);
        }
    });

    _context.register(BootstrapErrorRenderer, 'BootstrapErrorRenderer');

}, {
    DOM: 'Utils.DOM'
});
