// Copyright Zikula Foundation, licensed MIT.
(function($) {
    $(document).ready(function() {
        var formType = $('#zikulaprofilemodule_property_formType');
        formType.change(function() {
            $('#zikulaprofilemodule_property_formOptions').html('<i class="fa fa-cog fa-spin fa-3x fa-fw" aria-hidden="true"></i>');
            var $form = $(this).closest('form');
            var data = {};
            data[formType.attr('name')] = formType.val();
            $.ajax({
                url : $form.attr('action'),
                type: $form.attr('method'),
                data : data,
                success: function(html) {
                    $('#zikulaprofilemodule_property_formOptions').replaceWith(
                        $(html).find('#zikulaprofilemodule_property_formOptions')
                    );
                }
            });
        });
    });
})(jQuery);
