'use strict';
var root = $('#rootdir').val();

$(function(){
    $("body")
        //Открытие всплывающего окна редактирования пользователя
        .on('click', '.user_edit', function(e) {
            e.preventDefault();
            var userId = $(this).data('userid');
            var usergroupid = $(this).data('usergroup');

            switch(usergroupid)
            {
                case 2: getManagerPopup(userId);    break;
                case 4: getTeacherPopup(userId);    break;
                case 5: getClientPopup(userId);     break;
                case 6: getDirectorPopup(userId);   break;
                default:    break;
            }
        })
        .on('click', '.user_create', function(e) {
            e.preventDefault();
            var userId = 0;
            var usergroupid = $(this).data('usergroup');

            switch(usergroupid)
            {
                case 2: getManagerPopup(userId);    break;
                case 4: getTeacherPopup(userId);    break;
                case 5: getClientPopup(userId);     break;
                case 6: getDirectorPopup(userId);   break;
                default:    break;
            }
        })
        //Сохранение данных
        .on('click', '.popop_user_submit', function(e) {
            e.preventDefault();
            loaderOn();
            User.saveFrom('#createData', function(response) {
                if (response.error !== undefined) {
                    loaderOff();
                    notificationError(response.error.message);
                } else {
                    closePopup();
                    refreshUserTable();
                }
            });
        })
        //Всплывающее окно с параметрами отвала
        .on('click', '.user_activity', function(e) {
            var userId = $(this).data('userid');
            var userTr = $(this).parent().parent();
            var today = new Date();
            var dd = today.getDate();
            var mm = today.getMonth() + 1;

            var yyyy = today.getFullYear();
            if (dd < 10) {
                dd = '0' + dd;
            }
            if (mm < 10) {
                mm = '0' + mm;
            }
            var today = yyyy + '-' + mm + '-' + dd;
            var popupData = $( "" +
                "<form name=\"createData\" id=\"createData\" action=\".\" novalidate=\"novalidate\">" +
                "<div class=\"col-md-8\"><h4>Дата отвала:</h4><input type=\"date\" class=\"form-control\" id=\"date\" name=\"date_from\" value="+today+"></div>" +
                "<div class=\"col-md-8\"><h4>Причина:</h4><select class=\"form-control\" id=\"mainPropertyList\"></select></div>" +
                "<button class=\"user_archive btn btn-default\" data-userid = "+userId+">В архив</button>" +
                "</form>");
            var mainPropertyList = popupData.find('#mainPropertyList');
            PropertyList.clearCache(60);
            PropertyList.getList(60,
                function(response) {
                    $.each(response, function(key, property){
                        mainPropertyList.append('<option value="'+property.id+'">' + property.value + '</option>');
                    });
            });
            showPopup(popupData);
        })
        //Добавление пользователя в архив
        .on('click', '.user_archive', function(e) {
            e.preventDefault();
            var agree = confirm('Перенести пользователя в архив?');
            if (agree != true) return;
            loaderOn();
            var userId = $(this).data('userid');
            var userTr = $('#user_'+userId+'');
            if (!userTr.length){
                userTr = $(this).parent().parent();
           }


            if($('#mainPropertyList').val()!== undefined){
                User.archiveUser(userId,$('#mainPropertyList').val(),$('#date').val());
            }
            updateActive('User', userId, 'false', function(response) {
                var
                    totalCountSpan =    $('#total-clients-count'),
                    totalCount =        Number(totalCountSpan.text());

                userTr.remove();
                totalCountSpan.text(totalCount - 1);
                loaderOff();
            });
            closePopup();
        })
        //"Разархивирование пользователя"
        .on('click', '.user_unarchive', function(e) {
            e.preventDefault();
            var agree = confirm('Убрать пользователя из архива?');
            if (agree != true) return;
            loaderOn();
            var userId = $(this).data('userid');
            var userTr = $(this).parent().parent();
            updateActive('User', userId, 'true', function(response) {
                var
                    totalCountSpan =    $('#total-clients-count'),
                    totalCount =        Number(totalCountSpan.text());

                totalCountSpan.text(totalCount - 1);
                userTr.remove();
            });
        })
        //Удаление пользователя
        .on('click', '.user_delete', function(e) {
            e.preventDefault();
            var userId = $(this).data('model_id');
            deleteItem('User', userId, refreshArchiveTable);
        })
        //Нажатие на кнопку закрытия высплывающего окна редактирования пользователя
        .on("click", ".popup_close", function(e) {
            e.preventDefault();
            closePopup();
        })
        //Начисление платежа пользователю (форма)
        // .on('click', '.user_add_payment', function(e) {
        //     e.preventDefault();
        //     var userId = $(this).data('userid');
        //     getPaymentPopup(userId, root + '/user/client');
        // })
        //Сохранение заметок клиента
        .on('blur', '#client_notes', function() {
            loaderOn();
            var note = $(this).val();
            var userId = $(this).data('userid');
            updateUserNote(userId, note, loaderOff);
        })
        .on('click', '#per_lesson', function() {
            loaderOn();
            var value = 0;
            if ($(this).is(':checked')) {
                value = 1;
            }
            var userId = $(this).data('userid');
            updateUserPerLesson(userId, value, loaderOff);
        })
        .on('click', 'input[name=stop_list]', function(e) {
            loaderOn();
            let userId = $(this).data('user_id');
            let value = $(this).prop('checked');
            savePropertyValue('teacher_stop_list', value, 'User', userId, loaderOff);
        })
        //Сохранение логина клиента в личном кабинете
        .on("click", ".change_login_submit", function(e){
            e.preventDefault();
            loaderOn();
            saveData('User', function(response){ loaderOff(); });
            $('input[name=pass1]').val('');
            $('input[name=pass2]').val('');
        })
        .on('click', '.balance_show', function(e) {
            e.preventDefault();
            loaderOn();
            var dateFrom = $('input[name=date_from]').val();
            var dateTo = $('input[name=date_to]').val();
            $.ajax({
                type: 'GET',
                url: '',
                data: {
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: function(response) {
                    $('body').html(response);
                    loaderOff();
                }
            });
        })
        //Сохранеине комментария к клиенту
        .on('click', '#user_comment_save', function(e) {
            e.preventDefault();
            let commentText = $('#user_comment').val();
            let userId = $(this).data('userid');
            if (commentText != '') {
                loaderOn();
                //saveUserComment(userId, text, refreshUserTable);
                User.saveComment(userId, {text: commentText}, refreshUserTable);
            }
        })
        //Сохранение комментария к преподавателю
        .on('click', '#user_teacher_comment_save', function(e) {
            e.preventDefault();
            let commentText = $('#user_comment').val(),
                userId = $(this).data('userid');
            if (commentText != '') {
                loaderOn();
                User.saveComment(userId, {text: commentText}, refreshSchedule);
            }
        })
        .on('click', '#get_lid_data', function(e) {
            e.preventDefault();
            var lidId = $("#lid_id").val();
            if (lidId == '' || lidId == '0') {
                $('#lid_id').addClass('error');
                return false;
            }
            loaderOn();
            $('.popup').find('form').append('<input type="hidden" name="property_56[]" value="'+lidId+'" />');
            Lids.getLid(lidId, function (lid) {
                if (lid != '') {
                    $('input[name="name"]').val(lid.name);
                    $('input[name="surname"]').val(lid.surname);
                    $('input[name="phoneNumber"]').val(lid.number);
                    $('input[name="property_9[]"').val(lid.vk);
                    $('select[name="areas[]"').val(lid.area_id);
                    $('.get_lid_data_row').remove();
                } else {
                    notificationError("Лида с номером " + lidId + " не существует");
                }
                loaderOff();
            });
        })
        //Поиск клиента на странице менеджера
        .on('submit', '#search_client', function(e) {
            e.preventDefault();
            loaderOn();
            var
                surname = $('#surname').val(),
                name    = $('#name').val(),
                phone   = $('#phone').val();

            if(surname == '' && name == '' && phone == '') {
                loaderOff();
                return false;
            }

            searchClients(surname, name, phone);
        })
        .on('click', '#user_search_clear', function(e) {
            e.preventDefault();
            $('.dynamic-fixed-row').find('.buttons-panel').remove();
            $('.dynamic-fixed-row').find('.table-responsive').remove();
            $('#surname').val('');
            $('#name').val('');
            $('#phone').val('');
        })
        .on('click', '.info-by-id', function(e) {
            e.preventDefault();
            var model = $(this).data('model');
            var id = $(this).data('id');

            if (model == 'Lid') {
                loaderOn();
                Lids.getLid(id, function(lid){
                    var popupData = '<div class="popup-row-block cards-section section-lids text-center"></div>';
                    prependPopup(popupData, 80);
                    //$('.info-by-id')
                    prependLidCard(lid, $('.popup .section-lids'));
                    showPopup();
                    loaderOff();
                });
            } else {
                getObjectPopupInfo(id, model);
            }
        })
        .on('click', '.events_show', function(e) {
            e.preventDefault();
            var from = $('input[name="event_date_from"]').val();
            var to = $('input[name="event_date_to"]').val();
            loaderOn();
            $.ajax({type:'GET', url:'', data:{action:'refreshTableUsers',event_date_from:from,event_date_to:to}, success:function(response){
                $('.page').html(response);
                loaderOff();
            }});
        })
        .on('click', '.events_load_more', function() {
            loaderOn();
            var limit = $(this).data('limit');
            $.ajax({url:'', type:'GET', data:{action:'refreshTableUsers', limit: limit}, success:function(response){
                $('.page').html(response);
                loaderOff();
            }});
        })
        .on('click', '.edit_teacher_report', function(e) {
            e.preventDefault();
            var reportId = $(this).data('reportid');

            $.ajax({
                url: root + '/balance',
                type: 'GET',
                data: {
                    action: 'edit_report_popup',
                    id: reportId
                },
                success: function(response) {
                    showPopup(response);
                }
            });
        })
        .on('click', '.report_data_submit', function(e) {
            e.preventDefault();
            loaderOn();
            saveData('Main', function(response){ refreshUserTable(); });
        })
        .on('click', '#show-client-filter', function() {
            var
                form = $('#client-filter'),
                filters = form.find('.client-filter__options'),
                i    = $(this).find('i');

            if (filters.css('display') == 'none') {
                i.css('transform', 'rotate(180deg)');
                filters.show('fast');
            } else {
                i.css('transform', 'none');
                filters.hide('fast');
            }
        })
        .on('submit', '#client-filter', function(e) {
            e.preventDefault();
            applyClientFilter($('#client-filter'), function(response) {
                $('.users').html(response);
            });
        });
});

function changeClientsPage(page) {
    if (page > 0) {
        let form = $('#client-filter');
        form.append('<input type="hidden" name="page" value="'+page+'" />');
        applyClientFilter(form, function(response) {
            $('.users').html(response);
        });
    }
}


/**
 * Применение фильтров для поиска клиентов
 *
 * @param form
 * @param callback
 */
function applyClientFilter(form, callback) {
    loaderOn();
    var
        action =    form.attr('action'),
        data =      form.serialize();
    data += '&action=applyUserFilter';
    $.ajax({
        type: 'GET',
        url: action,
        data: data,
        success: function(response) {
            if(typeof callback === 'function') {
                callback(response);
            }
            loaderOff();
        }
    });
}


/**
 * Информация об объекте: пользователь, задача или лид
 *
 * @param id
 * @param model
 */
function getObjectPopupInfo(id, model) {
    loaderOn();
    $.ajax({
        url: root,
        type: 'GET',
        data: {
            action: 'getObjectInfoPopup',
            id: id,
            model: model
        },
        success: function(response) {
            showPopup(response);
            loaderOff();
        }
    });
}


/**
 * Поиск списка клиентов по фамилии, имени и номеру телефона
 *
 * @param surname
 * @param name
 * @param phone
 */
function searchClients(surname, name, phone) {
    $.ajax({
        url: root,
        type: 'GET',
        data: {
            action: 'search_client',
            surname: surname,
            name: name,
            phone: phone
        },
        success: function(response) {
            if (response == '') {
                notificationError('Пользователи с указаными параметрами не найдены');
            }
            var dynamicRow = $('.dynamic-fixed-row');
            $('.users').remove();
            dynamicRow.find('.buttons-panel').remove();
            dynamicRow.find('.table-responsive').remove();
            dynamicRow.append(response);
            loaderOff();
        }
    });
}


/**
 * Сохранение данных учетной записи клиента
 *
 * @param callback
 */
function userSave(callback) {
    var login = $('input[name=login]').val();
    var userId = $('input[name=id]').val();

    $.ajax({
        type: 'GET',
        url: root + '/user/client',
        data: {
            action: 'checkLoginExists',
            login: login,
            userId: userId
        },
        success: function(response) {
            if (response != '') {
                alert(response);
                loaderOff();
            } else {
                if ($('#createData').valid())
                    saveData('User', function(response) {
                        callback(response);
                    });
                else {
                    loaderOff();
                }
            }
        }
    });
}


/**
 * Сохранение значения доп. свойства пользователя - примечание
 *
 * @param userId
 * @param note
 * @param callback
 */
function updateUserNote(userId, note, callback) {
    $.ajax({
        type: 'GET',
        url: root + '/user/balance',
        data: {
            action: 'updateNote',
            userId: userId,
            note: note
        },
        success: function(response) {
            callback();
            if (response != '') {
                notificationError(response);
            }
        }
    });
}


/**
 * Изменение чекбокса клиента со значением свойства "поурочно"
 *
 * @param userId
 * @param value
 * @param callback
 */
function updateUserPerLesson(userId, value, callback) {
    $.ajax({
        type: 'GET',
        url: root + '/user/balance',
        data: {
            action: 'updatePerLesson',
            userId: userId,
            value: value
        },
        success: function(response) {
            callback();
            if (response != '') {
                notificationError(response);
            }
        }
    });
}


/**
 * Обновление контента страницы пользователей
 *
 * @returns {boolean}
 */
function refreshUserTable() {
    //Если это страница менеджера то просто происходит отправка данных формы поиска клиента
    //а там уже свой обработчик обновляет контент
    if ($('#search_client').length != 0) {
        $('#search_client').submit();
        return;
    }

    if ($('.users').length == 0) {
        loaderOff();
        return false;
    }

    $.ajax({
        type: 'GET',
        url: '',
        // async: false,
        data: {
            action: 'refreshTableUsers'
        },
        success: function(response) {
            $('.users').html(response);
            $('#sortingTable').tablesorter();
            loaderOff();
        }
    });
}


/**
 * Обновление контента страницы с архивом пользователей
 *
 * @param callback
 */
function refreshArchiveTable(callback) {
    $.ajax({
        type: 'GET',
        url: root + '/user/archive',
        data: {
            action: 'refreshTableArchive'
        },
        success: function(response) {
            $('.page').html(response);
            $('#sortingTable').tablesorter();
            if (typeof callback == 'function') {
                callback(response);
            }
        }
    });
}


/**
 * Открытие всплывающего окна редактирования данных клиента
 *
 * @param userId
 */
function getClientPopup(userId) {
    $.ajax({
        type: 'GET',
        url: root + '/user/client',
        data: {
            action: 'updateFormClient',
            userId: userId
        },
        success: function(response) {
            showPopup(response);
        }
    });
}


function makeClientPopup(userId, callback) {
    $.ajax({
        type: 'GET',
        url: root + '/user/client',
        data: {
            action: 'updateFormClient',
            userId: userId
        },
        success: function(response) {
            prependPopup(response);
            if (typeof callback == 'function') {
                callback();
            }
        }
    });
}


/**
 * Открытие всплывающего окна редактирования данных преподавателя
 *
 * @param userId
 */
function getTeacherPopup(userId) {
    $.ajax({
        type: 'GET',
        url: root + '/user/teacher',
        data: {
            action: 'updateFormTeacher',
            userId: userId
        },
        success: function(response) {
            showPopup(response);
        }
    });
}


/**
 * Открытие всплывающего окна редактирования данных директора
 *
 * @param userId
 */
function getDirectorPopup(userId) {
    $.ajax({
        type: 'GET',
        url: root + '/user/client',
        data: {
            action: 'updateFormDirector',
            userId: userId
        },
        success: function(response) {
            showPopup(response);
        }
    });
}


/**
 * Открытие всплывающего окна редактирования данных директора
 *
 * @param userId
 */
function getManagerPopup(userId) {
    $.ajax({
        type: 'GET',
        url: root + '/user/client',
        data: {
            action: 'updateFormManager',
            userId: userId
        },
        success: function(response) {
            showPopup(response);
        }
    });
}



function usersExport(href, form) {
    var link = href + '?action=export';

    if (form === undefined) {
        window.location.href = link;
    } else  {
        window.location.href = link + '&' + form.serialize();
    }
}



/*-----------------------------------------------*/
/*---------------Новые обработчики---------------*/
/*-----------------------------------------------*/

/**
 * Обработчик для редактирования количества занятий клиента
 *
 * @param userId
 * @param lessonsType
 * @param spanSelector
 */
function editClientCountLessons(userId, lessonsType, spanSelector) {
    var span = $(spanSelector);
    var currentCount = span.text();
    span.hide();
    span.parent().append('<input ' +
        'id="newCountLessonsVal" ' +
        'value="'+currentCount+'" ' +
        'class="form-control" ' +
        'style="width: 50px; display: inline-block" ' +
        'type="number"' +
        'step="0.5">');
    span.parent().append('<a ' +
        'class="action save"' +
        'id="saveCountLessons"' +
        'style="vertical-align: middle"' +
        'onclick="User.changeCountLessons('+userId+', User.OPERATION_SET, '+lessonsType+', $(\'#newCountLessonsVal\').val(), ' +
        'function(response){' +
        'var lessonsSpan = $(\''+spanSelector+'\');' +
        'lessonsSpan.text(response.newCount);' +
        'lessonsSpan.show();' +
        '$(\'#saveCountLessons\').remove();' +
        '$(\'#newCountLessonsVal\').remove();' +
        '})"' +
        '></a>');
}


function editClientRate(userId, rateName, spanSelector) {
    let span = $(spanSelector);
    let currentVal = span.text();
    span.hide();
    span.parent().append('<input ' +
        'id="newMedianaVal" ' +
        'value="'+currentVal+'" ' +
        'class="form-control" ' +
        'style="width: 50px; display: inline-block" ' +
        'type="number"' +
        'step="0.5">');
    span.parent().append('<a ' +
        'class="action save"' +
        'id="saveMedianaVal"' +
        'style="vertical-align: middle"' +
        'onclick="User.changeLessonsAvg(\''+userId+'\', \''+rateName+'\', User.OPERATION_SET, $(\'#newMedianaVal\').val(), ' +
        'function(response){' +
        'let lessonsSpan = $(\''+spanSelector+'\');' +
        'lessonsSpan.text($(\'#newMedianaVal\').val());' +
        'lessonsSpan.show();' +
        '$(\'#saveMedianaVal\').remove();' +
        '$(\'#newMedianaVal\').remove();' +
        '})"' +
        '></a>');
}


/**
 * Колбек при сохранении данных пользователя
 *
 * @param response
 */
function saveClientCallback(response) {
    if (typeof response.error !== 'undefined') {
        notificationError(response.error.message);
        loaderOff();
    } else {
        var tr = $('#user_' + response.user.id);
        if (tr.length == 0) {
            $('.table').prepend(makeClientTr(response));
            var prevLid = response.additional.prop_56.values[0].value;
            if (prevLid > 0) {
                Lids.getPrioritySetting(Lids.STATUS_CLIENT, function(status){
                    Lids.changeStatus(prevLid, status.id, function(response){
                        Lids.saveComment(0, prevLid, 'Добавлен в клиенты');
                    });
                });
            }
        } else {
            //ФИО
            tr.find('.user__fio').find('a').text(response.user.surname + ' ' + response.user.name);
            //Дата рождения
            tr.find('.user__birth').text(' ' + response.additional.prop_28.values[0].value + ' г.р.');
            //Соглашение подписано
            if (response.additional.prop_18.values[0].value == 1) {
                tr.find('.add__18').html('<span class="contract" title="Соглашение подписано"></span>');
            } else {
                tr.find('.add__18').empty();
            }
            //Телефоны
            tr.find('.user__phone').text(response.user.phone);
            tr.find('.add__16').text(response.additional.prop_16.values[0].value);
            //Баланс
            tr.find('.add__12').text(response.balance.amount);
            //Кол-во занятий
            tr.find('.add__13').text(response.balance.individual_lessons_count);
            tr.find('.add__14').text(response.balance.group_lessons_count);
            //Длительность занятия
            tr.find('.add__17').text(response.additional.prop_17.values[0].value);
            //Филиал
            if (response.areas.length > 0) {
                tr.find('.user__areas').text(response.areas[0].title);
            } else {
                tr.find('.user__areas').empty();
            }
        }
        closePopup();
        loaderOff();
    }
}


/**
 * Создание новой строчки в таблице пользователей
 *
 * @param data
 */
function makeClientTr(data) {
    let user = data.user,
        balance = data.balance,
        add = data.additional;

    var tr = $('<tr class="neutral" id="user_'+user.id+'" role="row"></tr>');
    var
        td1 = $('<td></td>'),
        td2 = $('<td></td>'),
        td3 = $('<td></td>'),
        td4 = $('<td></td>'),
        td5 = $('<td></td>'),
        td6 = $('<td></td>'),
        td7 = $('<td width="140px"></td>');

    //ФИО
    td1.append('<span class="user__fio"><a href="'+root+'/balance/?userid='+user.id+'">'+user.surname+' '+user.name+'</a></span>');
    //Соглашение подписано
    td1.append('<span class="add__18"></span>');
    if (add.prop_18.values[0].value == '1') {
        td1.find('.add__18').html('<span class="contract" title="Соглашение подписано"><input type="hidden"/></span>');
    }
    //Год рождения
    td1.append('<span class="user__birth"></span>');
    if (add.prop_28 !== undefined && add.prop_28.values[0].value != '') {
        td1.find('.user__birth').text(add.prop_28.values[0].value+' г.р.');
    }
    //Поурочная оплата
    td1.append('<span class="add__32"></span>');
    if (add.prop_32 !== undefined && add.prop_32.values[0].value == '1') {
        td1.find('.add__32').append('<div class="notes">«Поурочно»</div>');
    }
    //Примечание
    if (add.prop_19 != undefined && add.prop_19.values[0].value != '') {
        td1.append('<span class="add__19"><div class="notes">'+add.prop_19.values[0].value+'</div></span>');
    }

    //Номера телефонов
    td2.append('<span class="user__phone">'+user.phone+'</span>');
    td2.append('<br/><span class="add__16">'+add.prop_16.values[0].value+'</span>');

    //Баланс
    td3.append('<span class="add__12">'+balance.amount+'</span>');

    //Занятия
    td4.append('<span class="add__13">'+balance.individual_lessons_count+'</span>');
    td4.append(' / <span class="add__13">'+balance.group_lessons_count+'</span>');

    //Длительность занятия
    td5.append('<span class="add__17">'+add.prop_17.values[0].value+'</span>');

    //Филиал
    td6.append('<span class="user__areas"></span>');
    if (data.areas.length > 0) {
        td6.find('.user__areas').text(data.areas[0].title);
    }

    //Действия
    if (data.access.payment_create_client) {
        td7.append('<a class="action add_payment" onclick="makeClientPaymentPopup(0, '+user.id+', saveClientPaymentCallback)" title="Добавить платеж"></a>');
    }
    if (data.access.user_edit_client) {
        td7.append('<a class="action edit" onclick="getClientPopup('+user.id+')" title="Редактировать данные"></a>');
    }
    if (data.access.user_archive_client) {
        td7.append('<a class="action archive user_archive" data-userid="'+user.id+'" title="Переместить в архив"></a>');
    }

    tr.append(td1);
    tr.append(td2);
    tr.append(td3);
    tr.append(td4);
    tr.append(td5);
    tr.append(td6);
    tr.append(td7);
    return tr;
}


/**
 * Формирование списка тарифов для покупки из личного кабинета
 *
 * @param response
 */
function getClientLcTarifsCallBack(response) {
    if (response.error != undefined) {
        notificationError(response.error.message);
    } else {
        var select = $('<select class="form-control" id="tarif-list"></select>');
        $.each(response, function(key, tariff){
            select.append('<option value="'+tariff.id+'">'
                +tariff.title+' '+tariff.price+' р. индив: '+tariff.count_indiv+' групп: '+tariff.count_group
            +'</option>');
        });
        var btn = $('<button class="btn btn-default" ' +
            'onclick="Tarif.buyTariff(' +
                '$(\'#userid\').val(), ' +
                '$(\'#tarif-list\').find(\'option:selected\').val(), ' +
                'function(response){ ' +
                    'closePopup();' +
                    'if (response.error != undefined) { ' +
                        'notificationError(\'Ошибка: \' + response.message); ' +
                    '} else {' +
                    // '$(\'#balance\').text(Number($(\'#balance\').text()) - response.tarif.price);' +
                    '$(\'#balance\').text(response.user.balance.amount);' +
                    '$(\'#countLessonsIndiv\').text(response.user.balance.individual_lessons_count);' +
                    '$(\'#countLessonsGroup\').text(response.user.balance.group_lessons_count);' +
                    'if (response.user.balance.individual_lessons_average_price != undefined) { $(\'#medianaIdiv\').text(response.user.balance.individual_lessons_average_price); }' +
                    'if (response.user.balance.group_lessons_average_price != undefined) { $(\'#medianaGroup\').text(response.user.balance.group_lessons_average_price); }' +
                    'notificationSuccess(\'Тариф успешно приобретен\')' +
                '}}' +
            ')">Купить</button>');
        var row = $('<div class="row"></div>');
        row.append(select);
        row.append(btn);
        showPopup(row);
    }
    loaderOff();
}