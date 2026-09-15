// Minimal modal + tooltip/popover shims — erstatning for Bootstrap 3 JS.
// Holder eksisterende `data-toggle="modal"` / `data-dismiss="modal"` kørende
// uden Bootstrap; tooltips falder tilbage til native `title`-attribut.
(function ($) {
    if (typeof $ === 'undefined') return;

    $.fn.modal = function (action) {
        return this.each(function () {
            if (action === 'show') $(this).addClass('show');
            else if (action === 'hide') $(this).removeClass('show');
        });
    };

    // No-op shims: forhindrer `$(...).tooltip()` / `.popover()` i at kaste fejl.
    $.fn.tooltip = $.fn.tooltip || function () { return this; };
    $.fn.popover = $.fn.popover || function () { return this; };

    $(document).on('click', '[data-toggle="modal"]', function (e) {
        e.preventDefault();
        var target = $(this).data('target') || $(this).attr('href');
        if (target) $(target).addClass('show');
    });

    $(document).on('click', '[data-dismiss="modal"]', function () {
        $(this).closest('.modal').removeClass('show');
    });
})(window.jQuery);
