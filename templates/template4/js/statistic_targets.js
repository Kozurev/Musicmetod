initAjaxForm('#creditTargetAjaxForm', function(response) {
    ajaxFormSuccessCallbackDefault(response);
    $('#expensesTargetSpan').text(response.total);
});

$(document)
    .on('click', '.targetSave', function(e) {
        e.preventDefault();
        let $form = $('#creditTargetAjaxForm');
        $form.find('input[name=area_id]').val($(this).data('area_id'));
        $form.find('input[name=payment_type]').val($(this).data('payment_type'));
        $form.find('input[name=target]').val($(this).parent().parent().find('input[name=target]').val());
        $form.submit();
    });