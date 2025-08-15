/**
 * plugin admin area javascript
 */
(function($){$(function () {

    if ( ! $('body.wpallimport-plugin').length) return; // do not execute any code if we are not on plugin page

    $(document).on('click', 'a.add-new-line', function(){
        var $parent = $(this).parents('table').first();
        var $template = $parent.children('tbody').children('tr.template');
        var $clone = $template.clone(true);
        var $number = parseInt($parent.find('tbody:first').children().not('.template').length) - 1;

        var $cloneHtml = $clone.html().replace(/ROWNUMBER/g, $number).replace(/CELLNUMBER/g, 'ROWNUMBER').replace('date-picker', 'datepicker');

        $clone.html($cloneHtml);

        $clone.insertBefore($template).css('display', 'none').removeClass('template').fadeIn();

        $parent.on("change", "input.switcher", function (e) {
            if ($(this).is(':radio:checked')) {
                $(this).parents('form').find('input.switcher:radio[name="' + $(this).attr('name') + '"]').not(this).change();
            }
            let $targets = $('.switcher-target-' + $(this).attr('id'));
            let is_show = $(this).is(':checked'); if ($(this).is('.switcher-reversed')) is_show = ! is_show;
            if (is_show) {
                $targets.slideDown('fast', function(){
                    $(this).css({'overflow': 'visible'});
                });
            } else {
                $targets.slideUp().find('.clear-on-switch').add($targets.filter('.clear-on-switch')).val('');
            }
        }).change();

        $parent.on("change", "select.switcher", function (e) {
            var $targets = $('.switcher-target-' + $(this).attr('id'));
            var is_show = $(this).val() == 'xpath'; if ($(this).is('.switcher-reversed')) is_show = ! is_show;
            if (is_show) {
                $targets.slideDown();
            } else {
                $targets.slideUp().find('.clear-on-switch').add($targets.filter('.clear-on-switch')).val('');
            }
        }).change();

        // datepicker
        $parent.find('input.datepicker').removeClass('date-picker').addClass('datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            showOn: 'button',
            buttonText: '',
            constrainInput: false,
            showAnim: 'fadeIn',
            showOptions: 'fast'
        }).bind('change', function () {
            var selectedDate = $(this).val();
            var instance = $(this).data('datepicker');
            var date = null;
            if ('' != selectedDate) {
                try {
                    date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
                } catch (e) {
                    date = null;
                }
            }
            if ($(this).hasClass('range-from')) {
                $(this).parent().find('.datepicker.range-to').datepicker("option", "minDate", date);
            }
            if ($(this).hasClass('range-to')) {
                $(this).parent().find('.datepicker.range-from').datepicker("option", "maxDate", date);
            }
        }).change();
        $('.ui-datepicker').hide(); // fix: make sure datepicker doesn't break wordpress wpallimport-layout upon initialization

        return false;
    });

    $('a.add-new-line').each(function(){
        var $parent = $(this).parents('table:first');
        if ($(this).parents('table').length < 4 && $parent.children('tbody').children('tr').length == 2) {
            $(this).click();
        }
    });

    if ($('.switcher-target-pmgi_is_update_entry_fields').length) {
        var $re_import_options = $('.switcher-target-pmgi_is_update_entry_fields');
        var $toggle_re_import_options = $('.wpallimport-trigger-entry-fields');

        if ($re_import_options.find('input[type=checkbox]').length == $re_import_options.find('input[type=checkbox]:checked').length) {
            var $newtitle = $toggle_re_import_options.attr('rel');
            $toggle_re_import_options.attr('rel', $toggle_re_import_options.html());
            $toggle_re_import_options.html($newtitle);
            $toggle_re_import_options.removeClass('wpallimport-select-all');
        }
    }

    $('.wpallimport-trigger-entry-fields').click(function(){
        var $parent = $(this).parents('.switcher-target-pmgi_is_update_entry_fields:first');
        var $newtitle = $(this).attr('rel');
        if ( $(this).hasClass('wpallimport-select-all') ) {
            $parent.find('input[type=checkbox]').removeAttr('checked').click();
            $(this).removeClass('wpallimport-select-all');
        } else {
            $parent.find('input[type=checkbox]:checked').click();
            $(this).addClass('wpallimport-select-all');
        }
        $(this).attr('rel', $(this).html());
        $(this).html($newtitle);
    });

});})(jQuery);
