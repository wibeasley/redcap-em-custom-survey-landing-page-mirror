var CSLP = CSLP || {};

CSLP.init = function(elem) {
    var shortUrl = $('<code>').text(CSLP.surveyShortUrl);
    var longUrl = $('<code>').text(CSLP.surveyUrl);

    $('div.urls').append(shortUrl).append($("<div> -- OR -- </div>")).append(longUrl);
};