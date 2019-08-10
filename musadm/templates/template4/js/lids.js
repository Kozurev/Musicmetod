"use strict";
var root = $('#rootdir').val();

$(function () {
    $(document)
        .on('click', '.show_lid_status', function(e) {
            e.preventDefault();
            var statusesTable = $('.lid_statuses_table');

            if(statusesTable.css('display') == 'block') {
                statusesTable.hide();
            } else {
                statusesTable.show();
            }
        })
        .on('click', '.edit_lid_status', function(e) {
            e.preventDefault();
            var statusId = $(this).data('id');
            getLidStatusPopup(statusId);
        })
        .on('click', '.lid_status_submit', function(e) {
            e.preventDefault();

            var
                Form =      $('#createData'),
                id =        Form.find('input[name=id]').val(),
                title =     Form.find('input[name=title]').val(),
                itemClass = Form.find('select[name=item_class]').val();

            saveLidStatus(id, title, itemClass, function(response) {
                closePopup();

                var statusSelects = $('.lid_status');

                if(id == '') {
                    var newTr = '<tr>' +
                        '<td>' + response.title + '</td>' +
                        '<td>' + response.colorName + '</td>' +
                        '<td>' +
                        '   <input type="radio" name="lid_status_consult" id="lid_status_consult_'+response.id+'" value="'+response.id+'">' +
                        '   <label for="lid_status_consult_'+response.id+'"></label></td>' +
                        '<td>' +
                        '   <input type="radio" name="lid_status_consult_attended" id="lid_status_consult_attended_'+response.id+'" value="'+response.id+'">' +
                        '   <label for="lid_status_consult_attended_'+response.id+'"></label>' +
                        '</td>' +
                        '<td>' +
                        '   <input type="radio" name="lid_status_consult_absent" id="lid_status_consult_absent_'+response.id+'" value="'+response.id+'">' +
                        '   <label for="lid_status_consult_absent_'+response.id+'"></label>' +
                        '</td>' +
                        '<td class="right">' +
                            '<a class="action edit edit_lid_status" data-id="' + response.id + '"></a>' +
                            '<a class="action delete delete_lid_status" data-id="' + response.id + '"></a>' +
                        '</td>' +
                        '</tr>';

                    var
                        table = $('#table-lid-statuses'),
                        lastTr = table.find('tr')[table.find('tr').length - 1],
                        lastTrClone = $(lastTr).clone();

                    $(lastTr).remove();
                    table.append(newTr);
                    table.append(lastTrClone);

                    $.each(statusSelects, function(key, select) {
                        $(select).append('<option value="' + response.id + '">' + response.title + '</option>');
                    });
                } else {
                    var
                        tr = $('.lid_statuses_table').find('.edit[data-id='+id+']').parent().parent(),
                        tdTitle = tr.find('td')[0],
                        tdColor = tr.find('td')[1];

                    $(tdTitle).text(response.title);
                    $(tdColor).text(response.colorName);

                    $.each(statusSelects, function(key, select) {
                        $(select).find('option[value='+response.id+']').text(response.title);
                    });

                    if(response.oldItemClass) {
                        var editingStatusCards = $('.' + response.oldItemClass);
                        $.each(editingStatusCards, function(key, card) {
                            $(card).removeClass(response.oldItemClass);
                            $(card).addClass(response.itemClass);
                        });
                    }
                }
            });
        })
        .on('click', '.delete_lid_status', function(e) {
            e.preventDefault();

            var id = $(this).data('id');

            deleteLidStatus(id, function(response) {
                $('.lid_statuses_table').find('.edit[data-id='+response.id+']').parent().parent().remove();

                var statusSelects = $('.lid_status');
                $.each(statusSelects, function(key, select) {
                    $(select).find('option[value='+response.id+']').remove();
                });

                var deletedStatusItems = $('.' + response.itemClass);
                $.each(deletedStatusItems, function(key, card) {
                    $(card).removeClass(response.itemClass);
                });
            });
        })
        .on('change', '#source_select', function(e) {
            var sourceInput = $('#source_input');

            if($(this).val() == 0) {
                sourceInput.show();
            } else {
                sourceInput.val('');
                sourceInput.hide();
            }
        });


        $('#table-lid-statuses').on('change', 'input[type=radio]', function() {
            var
                propName =      $(this).attr('name'),
                propVal =       $(this).val(),
                directorId =    $('#directorid').val(),
                statusName =    $(this).parent().parent().find('td')[0];
            statusName = $(statusName).text();

            savePropertyValue(propName, propVal, 'User', directorId, function() {
                var msg = 'Статусом лида после ';

                switch(propName)
                {
                    case 'lid_status_consult':
                        msg += 'создания консультации';
                        break;
                    case 'lid_status_consult_attended':
                        msg += 'посещения консультации';
                        break;
                    case 'lid_status_consult_absent':
                        msg += 'пропуска консультации';
                        break;
                    case 'lid_status_client' :
                        msg += 'записи';
                        break;
                    default: msg = 'Неизвестной настройке';
                }

                msg += ' установлен: \''+ statusName +'\'';
                notificationSuccess(msg);
            });
        });
});


/**
 * Перезагрузка блока с классом lids
 */
function refreshLidTable() {
    // var dateFrom = $("input[name=date_from]").val();
    // var dateTo = $("input[name=date_to]").val();
    // var areaId = $('select[name=area_id]').val();
    let filtersForm = $('#filter_lids');
    let data = filtersForm.serialize();
    data += '&action=refreshLidTable';

    $.ajax({
        type: 'GET',
        url: '',
        async: false,
        data: data,
        success: function (response) {
            $('.lids').html(response);
            loaderOff();
        }
    });
}



/**
 * Открытие всплывающего окна создания/редактирования статуса лида
 *
 * @param id
 */
function getLidStatusPopup(id) {
    loaderOn();
    $.ajax({
        type: 'GET',
        url: root + '/lids',
        data: {
            action: 'getLidStatusPopup',
            id: id
        },
        success: function(response) {
            showPopup(response);
            loaderOff();
        },
        error: function(response) {
            closePopup();
            notificationError('Ошибка: редактируемый статус не существует либо принадлежит другой организации');
            loaderOff();
        }
    });
}


/**
 * Создание/редактирование данных статуса лида
 *
 * @param id
 * @param title
 * @param itemClass
 * @param callback
 */
function saveLidStatus(id, title, itemClass, callback) {
    loaderOn();

    $.ajax({
        type: 'GET',
        url: root + '/lids',
        dataType: 'json',
        data: {
            action: 'saveLidStatus',
            id: id,
            title: title,
            item_class: itemClass
        },
        success: function(response) {
            callback(response);
            loaderOff();
        },
        error: function(response) {
            notificationError('При сохранении статуса лида произошла ошибка');
        }
    });
}


/**
 * Удаление статуса лида
 *
 * @param id
 * @param callback
 */
function deleteLidStatus(id, callback) {
    loaderOn();

    $.ajax({
        type: 'GET',
        url: root + '/lids',
        dataType: 'json',
        data: {
            action: 'deleteLidStatus',
            id: id
        },
        success: function(response) {
            callback(response);
            loaderOff();
        },
        error: function(response) {
            closePopup();
            notificationError('Ошибка: удаляемый статус не существует либо принадлежит другой организации');
            loaderOff();
        }
    });
}



/*---------------------------------------------------------------------------------------------*/
/*--------------------------------------Новые обработчики--------------------------------------*/
/*---------------------------------------------------------------------------------------------*/
$(function(){
    $('body')
        //Обработчик события поиска лидов по заданным параметрам
        .on('click', '.lids_search', function(e) {
            e.preventDefault();
            loaderOn();
            //let filtersForm = $('#filter_lids');
            //let data = filtersForm.serialize();
            //data += '&action=refreshLidTable';
            refreshLidTable();
            // let params = {filter: {}};
            //
            // let periodFrom = filtersForm.find('input[name=date_from]').val();
            // let periodTo = filtersForm.find('input[name=date_to]').val();
            // let id = filtersForm.find('input[name=id]').val();
            // let number = filtersForm.find('input[name=number]').val();
            // let statusId = filtersForm.find('select[name=status_id]').val();
            // let areaId = filtersForm.find('select[name=area_id]').val();
            //
            // if (id == '' && number == '') {
            //     params['date_from'] = periodFrom;
            //     params['date_to'] = periodTo;
            // }
            // if (statusId > 0) {
            //     params.filter['status_id'] = statusId;
            // }
            // if (areaId > 0) {
            //     params.filter['area_id'] = areaId;
            // }
            // if (id > 0) {
            //     params.filter['id'] = id;
            // }
            // if (number != '') {
            //     params.filter['number'] = number;
            // }
            //
            // params.order = {priority_id: 'ASC', id: 'ASC'};
            // params.select = ['property_50', 'property_54'];

            // Lids.clearCache();
            // Schedule.clearCache();
            //
            // Lids.getList(params, function(lids){
            //     console.log(lids);
            //     let lidsBlock = $('.section-lids').find('.row');
            //     lidsBlock.empty();
            //     $.each(lids, function(key, lid){
            //         prependLidCard(lid, lidsBlock);
            //     });
            //     loaderOff();
            // });
        })
        //Обновление данных страницы аналитики лидов
        .on('click', '.lids_statistic_show', function(e){
            e.preventDefault();
            loaderOn();
            let formData = $('#filter_lids_statistic').serialize();
            $.ajax({
                type: 'GET',
                url: root + '/lids/statistic?action=refresh',
                data: formData,
                success: function (response) {
                    $('.lids').html(response);
                    loaderOff();
                },
                error: function () {
                    notificationError('Произошла ошибка');
                    loaderOff();
                }
            });
        })
        .on('click', '.lids_consult_show', function(e){
            e.preventDefault();
            loaderOn();
            let formData = $('#filter_lids').serialize();
            $.ajax({
                type: 'GET',
                url: root + '/lids/consults?action=refresh',
                data: formData,
                success: function (response) {
                    $('.lids').html(response);
                    loaderOff();
                },
                error: function () {
                    notificationError('Произошла ошибка');
                    loaderOff();
                }
            });
        });
});



/**
 * Формирование всплывающего окна создания/редактирования лида
 *
 * @param lidId
 */
function makeLidPopup(lidId) {
    loaderOn();

    //Поиск информации о лиде
    Lids.getLid(lidId, function(lid){
        let popupData =
            '<div class="popup-row-block" id="editLidForm">' +
            '<div class="column"><span>Фамилия</span></div>' +
            '<div class="column"><input class="form-control" type="text" name="surname" value="'+lid.surname+'"></div>' +
            '<hr>' +
            '<div class="column"><span>Имя</span></div>' +
            '<div class="column"><input class="form-control" type="text" name="name" value="'+lid.name+'"></div>' +
            '<hr>' +
            '<div class="column"><span>Номер телефона</span></div>' +
            '<div class="column"><input class="form-control masked-phone" type="text" name="number" value="'+lid.number+'"></div>' +
            '<hr>' +
            '<div class="column"><span>Ссылка ВК</span></div>' +
            '<div class="column"><input class="form-control" type="text" name="vk" value="'+lid.vk+'"></div>' +
            '<hr>' +
            '<div class="column"><span>Дата контроля</span></div>' +
            '<div class="column"><input class="form-control" type="date" name="control_date" value="'+lid.control_date+'"></div>' +
            '<hr>' +
            '<div class="column"><span>Статус</span></div>' +
            '<div class="column">' +
                '<select class="form-control" name="status_id" id="status_id">' +
                '</select>' +
            '</div>' +
            '<hr>' +
            '<div class="column"><span>Филиал</span></div>' +
            '<div class="column">' +
                '<select class="form-control" name="area_id" id="area_id">' +
                    '<option value="0"> ... </option>' +
                '</select>' +
            '</div>' +
            '<hr>' +
            '<div class="column"><span>Маркер</span></div>' +
            '<div class="column">' +
                '<select class="form-control" name="property_54" id="property_54">' +
                    '<option value="0"> ... </option>' +
                '</select>' +
            '</div>' +
            '<hr>' +
            '<div class="column"><span>Источник</span></div>' +
            '<div class="column">' +
                '<select class="form-control" name="property_50" id="source_select">' +
                    '<option value="0">Другое</option>' +
                '</select>' +
                '<input class="form-control" type="text" value="'+lid.source+'" id="source_input" name="source" placeholder="Источник">' +
            '</div>' +
            '<hr>' +
            '<div class="column"><span>Приоритет</span></div>' +
            '<div class="column">' +
                '<select class="form-control" name="priorityId" id="priorityId">' +
            '</select>' +
            '</div>';

        if (lidId == 0) {
            popupData +=
                '<hr>' +
                '<div class="column"><span>Комментарий</span></div>' +
                '<div class="column"><textarea class="form-control" name="comment"></textarea></div>';
        }

        popupData +=
            '<input type="hidden" value="'+lid.id+'" name="id" id="id" />' +
            '<button class="btn btn-default" onclick="saveLidFrom($(\'#editLidForm\'), saveLidCallback)">Сохранить</button>' +
            '</div>';

        prependPopup(popupData);

        let isSelected;

        Schedule.clearCache();
        Lids.clearCache();
        PropertyList.clearCache(50);
        PropertyList.clearCache(54);

        //Подгрузка списка источников
        PropertyList.getList(54, function(markers){
            if (typeof markers.error != 'undefined') {
                notificationError(markers.error.message);
                return false;
            } else {
                let markersList = $('#property_54');
                $.each(markers, function(key, marker){
                    isSelected = marker.id == lid.property_54[0].value_id ? 'selected' : '';
                    markersList.append('<option value="'+marker.id+'" '+isSelected+'>'+marker.value+'</option>');
                });

                //Подгрузка списка маркеров
                PropertyList.getList(50, function(sources){
                    if (typeof sources.error != 'undefined') {
                        notificationError(sources.error.message);
                        return false;
                    } else {
                        let sourceList = $('#source_select');
                        $.each(sources, function(key, source){
                            isSelected = source.id == lid.property_50[0].value_id ? 'selected' : '';
                            sourceList.append('<option value="'+source.id+'" '+isSelected+'>'+source.value+'</option>');
                            if (isSelected != '') {
                                $('#source_input').css('display', 'none');
                            }
                        });

                        //Подгрузка статусов
                        Lids.getStatusList(function(statuses){
                            let statusList = $('#status_id');
                            $.each(statuses, function(key, status){
                                isSelected = status.id == lid.status_id ? 'selected' : '';
                                statusList.append('<option value="'+status.id+'" '+isSelected+'>'+status.title+'</option>');
                            });

                            //Подгрузка филиалов
                            Schedule.getAreasList({isRelated:true}, function(areas){
                                let areasList = $('#area_id');
                                $.each(areas, function(key, area){
                                    isSelected = area.id == lid.area_id ? 'selected' : '';
                                    areasList.append('<option value="'+area.id+'" '+isSelected+'>'+area.title+'</option>');
                                });

                                Lids.getPriorityList(function(priorities){
                                    let priorityList = $('#priorityId');
                                    $.each(priorities, function(key, priority){
                                        isSelected = priority.id == lid.priority_id ? 'selected' : '';
                                        priorityList.append('<option value="'+priority.id+'" '+isSelected+'>'+priority.title+'</option>');
                                    });
                                });
                                showPopup();
                                loaderOff();
                            });
                        });
                    }
                });
            }
        });
    });
}


/**
 * Рендеринг новой карточки лида и добавление её в начало родительского блока
 *
 * @param lid
 * @param block
 */
function prependLidCard(lid, block) {
    let isSelected, style;
    let card =
        '<div class="item lid_'+lid.id+'">' +
            '<div class="item-inner">' +
                '<div class="row">' +
                    '<div class="col-sm-3 col-xs-12">' +
                        '<h3 class="title">' +
                            '<span class="id">'+lid.id+' </span>' +
                            '<span class="surname">'+lid.surname+' </span>' +
                            '<span class="name">'+lid.name+' </span>' +
                        '</h3>';

                        style = lid.number == '' ? 'style="display:none"' : '';
                        card += '<p class="intro" '+style+'><span class="number">'+lid.number+'</span></p>';

                        style = lid.vk == '' ? 'style="display:none"' : '';
                        card += '<p class="intro" '+style+'><span>ВК: </span><span class="vk">'+lid.vk+'</span></p>';
        card +=
                        '<input type="date" class="form-control date_inp lid_date" onchange="Lids.changeDate('+lid.id+', this.value)" value="'+lid.control_date+'">' +
                        '<select name="status" class="form-control lid_status" onchange="Lids.changeStatus('+lid.id+', this.value, changeLidStatusCallback)">' +
                            '<option value="0"> ... </option>' +
                        '</select>' +
                        '<select class="form-control lid-area" onchange="Lids.changeArea('+lid.id+', this.value)">' +
                            '<option value="0"> ... </option>' +
                        '</select>' +
                        '<select class="form-control lid_priority" onchange="Lids.changePriority('+lid.id+', this.value)">' +
                        '</select>';

                        style = empty(lid.property_54[0].value_id) ? 'style="display:none"' : '';
                        card += '<p class="intro" '+style+'><span>Маркер: </span><span class="marker">'+lid.property_54[0].value+'</span></p>';

                        style = empty(lid.source) && empty(lid.property_50[0].value_id) ? 'style="display:none"' : '';
                        let source = '';
                        if (!empty(lid.source) || !empty(lid.property_50[0].value_id)) {
                            if (lid.source != '') {
                                source = lid.source;
                            } else {
                                source = lid.property_50[0].value;
                            }
                        }
                        card += '<p class="intro" '+style+'><span>Источник: </span><span class="source">'+source+'</span></p>';

        card +=
                        '<a class="action edit" onclick="makeLidPopup('+lid.id+')" title="Редактировать лида"></a>' +
                        '<a class="action comment" title="Добавить комментарий" onclick="makeLidCommentPopup(0, '+lid.id+', saveLidCommentCallback)"></a>' +
                        '<a class="action add_user" title="Создать пользователя" onclick="makeClientFromLidPopup('+lid.id+')"></a>' +
                    '</div>' +
                    '<div class="col-sm-9 col-xs-12 comments-column">' +
                        '<div class="comments">';
                            $.each(lid.comments, function(key, comment){
                                card += makeLidCommentBlock(comment);
                            });
    card +=
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';

    block.prepend(card);
    let lidCard = block.find('.lid_' + lid.id);

    //Подгрузка статусов
    Lids.getStatusList(function (statuses) {
        let statusList = lidCard.find('.lid_status');
        $.each(statuses, function (key, status) {
            isSelected = status.id == lid.status_id ? 'selected' : '';
            if (isSelected != '') {
                lidCard.addClass(status.item_class);
            }
            statusList.append('<option value="' + status.id + '" ' + isSelected + '>' + status.title + '</option>');
        });

        //Подгрузка филиалов
        Schedule.getAreasList({isRelated: true}, function (areas) {
            let areasList = lidCard.find('.lid-area');
            $.each(areas, function (key, area) {
                isSelected = area.id == lid.area_id ? 'selected' : '';
                areasList.append('<option value="' + area.id + '" ' + isSelected + '>' + area.title + '</option>');
            });

            //Подгрузка приоритетов
            Lids.getPriorityList(function (priorities) {
                let priorityList = lidCard.find('.lid_priority');
                $.each(priorities, function (key, priority) {
                    isSelected = priority.id == lid.priority_id ? 'selected' : '';
                    priorityList.append('<option value="' + priority.id + '" ' + isSelected + '>' + priority.title + '</option>');
                });
            });
        });
    });
}


/**
 * ФОрмирование HTML блока комментария
 *
 * @param comment
 * @returns {string}
 */
function makeLidCommentBlock(comment) {
    return '<div class="block">' +
            '<div class="comment_header">' +
                '<div class="author">'+comment.author_fullname+'</div>' +
                '<div class="date">'+comment.refactoredDatetime+'</div>' +
            '</div>' +
            '<div class="comment_body">'+comment.text+'</div>' +
        '</div>';
}


function makeLidCommentPopup(commentId, lidId, callback) {
    let popupData =
        '<div class="popup-row-block">' +
        '<div class="column"><span>Комментарий</span></div>' +
        '<div class="column"><textarea name="text" id="lidCommentText"></textarea></div>' +
        '<button class="btn btn-default" ' +
        'onclick="Lids.saveComment('+commentId+', '+lidId+', $(\'#lidCommentText\').val(), '+callback+')">Сохранить</button>' +
        '</div>';
    showPopup(popupData);
}


function saveLidCommentCallback(comment) {
    if (comment.status == false) {
        notificationError(comment.message);
        loaderOff();
    } else {
        $('.lid_' + comment.lid_id).find('.comments').prepend(makeLidCommentBlock(comment));
    }
    closePopup();
}


/**
 * Сохранение данных лида с формы
 *
 * @param form
 * @param callback
 */
function saveLidFrom(form, callback) {
    loaderOn();
    let lidData = {};
    lidData.id = form.find('input[name=id]').val();
    lidData.surname = form.find('input[name=surname]').val();
    lidData.name = form.find('input[name=name]').val();
    lidData.number = form.find('input[name=number]').val();
    lidData.vk = form.find('input[name=vk]').val();
    lidData.controlDate = form.find('input[name=control_date]').val();
    lidData.statusId = form.find('select[name=status_id]').val();
    lidData.areaId = form.find('select[name=area_id]').val();
    lidData.source = form.find('input[name=source]').val();
    lidData.priorityId = form.find('select[name=priorityId]').val();
    lidData.property_50 = form.find('select[name=property_50]').val();
    lidData.property_54 = form.find('select[name=property_54]').val();
    let comment = form.find('textarea[name=comment]');
    if (comment.length > 0) {
        lidData.comment = comment.val();
    }
    Lids.save(lidData, callback);
}


/**
 * Колбек функция при сохранении лида
 *
 * @param lid
 */
function saveLidCallback(lid) {
    let lidsSection = $('.section-lids').find('.cards-wrapper'),
        lidCard = $('.lid_' + lid.id);

    if (lidCard.length == 0) {
        prependLidCard(lid, lidsSection);
    } else {
        lidCard.find('.surname').text(lid.surname + ' ');
        lidCard.find('.name').text(lid.name + ' ');

        let number = lidCard.find('.number');
        if (lid.number == '') {
            number.empty();
            number.parent().hide();
        } else {
            number.text(lid.number);
            number.parent().show();
        }

        let vk = lidCard.find('.vk');
        if (lid.vk == '') {
            vk.empty();
            vk.parent().hide();
        } else {
            vk.text(lid.vk);
            vk.parent().show();
        }

        let marker = lidCard.find('.marker');
        if (lid.property_54[0].value_id == 0) {
            marker.empty();
            marker.parent().hide();
        } else {
            marker.text(lid.property_54[0].value);
            marker.parent().show();
        }

        let source = lidCard.find('.source');
        if (lid.source == '' && lid.property_50[0].value_id == 0) {
            source.empty();
            source.parent().hide();
        } else {
            if (lid.source != '') {
                source.text(lid.source);
            } else {
                source.text(lid.property_50[0].value);
            }
            source.parent().show();
        }

        lidCard.find('.lid_date').val(lid.control_date);
        lidCard.find('.lid-area').val(lid.area_id);
        lidCard.find('.lid_priority').val(lid.priority_id);
        lidCard.find('.lid_status').val(lid.status_id);
        if (lid.status_id > 0) {
            lidCard.attr('class', 'item ' + lid.status.item_class + ' lid_' + lid.id);
        }
    }
    closePopup();
    loaderOff();
}


function changeLidStatusCallback(response) {
    if (response.status == false) {
        notificationError(response.message);
    } else {
        let lidCard = $('.lid_' + response.lid.id);
        lidCard.attr('class', 'item ' + response.status.item_class + ' lid_' + response.lid.id);
    }
}


function makeClientFromLidPopup(lidId) {
    loaderOn();
    makeClientPopup(0, function () {
        $('#lid_id').val(lidId);
        $('#get_lid_data').trigger('click');
        localStorage.setItem('clientFromLidId', lidId);
        $('.popup').find('.btn-default').attr('onclick', 'User.saveFrom($(\'#createData\'), makeClientFromLidCallback)');
        showPopup();
    });
}


function makeClientFromLidCallback(client) {
    if (client.error == undefined) {
        Lids.getPrioritySetting(Lids.STATUS_CLIENT, function(status){
            let lidId = localStorage.getItem('clientFromLidId');
            localStorage.removeItem('clientFromLidId');
            Lids.changeStatus(lidId, status.id, function(response){
                changeLidStatusCallback(response);
                loaderOff();
                closePopup();
            });
        });
    } else {
        notificationError(client.error.message);
        loaderOff();
    }
}