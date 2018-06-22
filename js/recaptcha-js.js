var recaptchaOnloadCallback = function() {
    jQuery(".g-recaptcha").each(function() {
        var reCaptcha = jQuery(this);
        if (reCaptcha.data("recaptcha-client-id") === undefined) {
            var recaptchaClientId = grecaptcha.render(reCaptcha.attr("id"), {
                "callback": function(response) {
                    if (reCaptcha.attr("form-id") !== "") {
                        jQuery("#" + reCaptcha.attr("input-id"), "#" + reCaptcha.attr("form-id")).val(response).trigger("change");
                    } else {
                        jQuery("#" + reCaptcha.attr("input-id")).val(response).trigger("change");
                    }

                    if (reCaptcha.attr("data-callback")) {
                        eval("(" + reCaptcha.attr("data-callback") + ")(response)");
                    }
                },
                "expired-callback": function() {
                    if (reCaptcha.attr("form-id") !== "") {
                        jQuery("#" + reCaptcha.attr("input-id"), "#" + reCaptcha.attr("form-id")).val("");
                    } else {
                        jQuery("#" + reCaptcha.attr("input-id")).val("");
                    }

                    if (reCaptcha.attr("data-expired-callback")) {
                        eval("(" + reCaptcha.attr("data-expired-callback") + ")()");
                    }
                },
            });
            reCaptcha.data("recaptcha-client-id", recaptchaClientId);

            if (reCaptcha.data("size") === "invisible") {
                grecaptcha.execute(recaptchaClientId);
            }
        }
    });
};