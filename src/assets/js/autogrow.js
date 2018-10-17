(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.Autogrow = factory();
    }
})(typeof self !== 'undefined' ? self : this, function() {

    function Autogrow(elem) {
        this._handleInput = this._handleInput.bind(this);
        this.setElement(elem);
    }

    Autogrow.prototype = {
        constructor: Autogrow,

        setElement: function (elem) {
            if (this._elem) {
                this._elem.removeEventListener('input', this._handleInput);
            }

            this._elem = elem;
            this._elem.style.overflow = 'hidden';
            this._elem.addEventListener('input', this._handleInput);
            this._handleInput();
        },

        destroy: function () {
            this._elem.removeEventListener('input', this._handleInput);
            this._elem = this._handleInput = null;
        },

        _handleInput: function () {
            if (this._elem.scrollHeight > this._elem.clientHeight) {
                this._elem.style.height = (this._elem.scrollHeight + 1) + 'px';
            }
        }
    };

    return Autogrow;

});
