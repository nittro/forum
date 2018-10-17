_context.invoke(function (DOM) {

    var $d = $(document);

    $d.on('click', '[data-toggle="class"]', function (evt) {
        evt.preventDefault();

        var target = DOM.getData(evt.currentTarget, 'target'),
            className = DOM.getData(evt.currentTarget, 'class', 'active');

        $(target).toggleClass(className);
    });

    $d.on('click', '.sidebar.sidebar-visible a', function () {
        $('.sidebar').removeClass('sidebar-visible');
    });

}, {
    DOM: 'Utils.DOM'
});
