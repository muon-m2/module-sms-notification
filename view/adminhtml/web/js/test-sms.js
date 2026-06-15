define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function () {
            var $result = $(config.resultSelector);

            $result.removeClass('muon-sms-ok muon-sms-err').text('');

            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: { form_key: window.FORM_KEY },
                showLoader: true
            }).done(function (response) {
                $result
                    .addClass(response.success ? 'muon-sms-ok' : 'muon-sms-err')
                    .text(response.message);
            }).fail(function () {
                $result.addClass('muon-sms-err').text('Request failed.');
            });
        });
    };
});
