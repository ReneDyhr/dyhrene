function openNav() {
    document.getElementById("mySidenav").style.width = "200px";
    document.getElementById("mySidenav").style.padding = "20px";
    document.getElementById("main").style.marginLeft = "200px";
    $("#opennav").hide();
    $("#closenav").show();
    // $("html").css("overflow", "hidden");
    $("body").css("overflow", "hidden");
    $("body").css("position", "relative");
}

function closeNav() {
    $('#mySidenav').attr('style', '');
    $('#main').attr('style', '');
    $('html').attr('style', '');
    $('body').attr('style', '');
    $("#closenav").hide();
    $("#opennav").show();
}

$(document).on('swiperight', function () {
    if ($(window).width() <= 740) {
        openNav();
    }
});
$(document).on('swipeleft', function () {
    if ($(window).width() <= 740) {
        closeNav();
    }
});