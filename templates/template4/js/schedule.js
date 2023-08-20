"use strict";
var root = $('#rootdir').val();

$(function(){
    let days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];

    //Отмена перехода по ссылке при клике на элемент выпадающего списка
    $('.submenu').on('click', 'a', function(e){ e.preventDefault(); });

    $('body')
        //Подгрузка данных расписания при изменении даты в календаре
        .on('change', '.schedule_calendar', function() {
            loaderOn();
            let
                date = $(this).val(),
                userid = $('#userid').val(),
                newDate = new Date($(this).val()),
                dayName = days[newDate.getDay()];
            $('.day_name').text(dayName);
            getSchedule(userid, date, loaderOff);
        })

        //Открытие всплывающего окна создания периода отсутствия
         .on('click', '.schedule_absent', function(e) {
            e.preventDefault();
            let
                userId =  $(this).parent().parent().data('clientid'),
                typeId =    $(this).parent().parent().data('typeid'),
                date =      $('#schedule_calendar').val();
            getScheduleAbsentPopup(userId, typeId, date);
        })

         //Занесение преподавателя в стоп-лист
        .on('click', 'input[name=teacher_stop_list]', function() {
            let userId = $(this).data('user_id');
            let value = $(this).prop('checked');
            savePropertyValue('teacher_stop_list', value, 'User', userId, loaderOff);
        })

        //Сохранение данных периода отсутствия
        .on('click', '.popop_schedule_absent_submit', function(e) {
            e.preventDefault();
            loaderOn();

            let
                form = $('#createData'),
                absentData = {};

            absentData.id = form.find('input[name=id]').val();
            absentData.object_id = form.find('input[name=object_id]').val();
            absentData.date_from = form.find('input[name=date_from]').val();
            absentData.date_to = form.find('input[name=date_to]').val();
            absentData.time_from = form.find('input[name=time_from]').val();
            absentData.time_to = form.find('input[name=time_to]').val();
            absentData.type_id = form.find('input[name=type_id]').val();

            Schedule.saveAbsentPeriod(absentData, function (response) {
                if (response.status !== false) {
                    if ($('#absent_add_task').is(':checked')) {
                        addAbsentTask(absentData.date_to, absentData.object_id);
                    }

                    let msg = 'Период отсутствия с ' + response.absent.refactoredDateFrom + ' ';
                    if (response.absent.refactoredTimeFrom != '00:00') {
                        msg += response.absent.refactoredTimeFrom;
                    }
                    msg += ' по ' + response.absent.refactoredDateTo + ' ';
                    if (response.absent.refactoredTimeTo != '00:00') {
                        msg += response.absent.refactoredTimeTo;
                    }
                    msg += ' успешно сохранен';

                    notificationSuccess(msg);
                    closePopup();
                    if ($('.users').length == 0) {
                        refreshSchedule();
                    } else {
                        refreshUserTable();
                    }
                } else {
                    let $popup = $('.popup');
                    let $popupError = $popup.find('p.error');
                    if ($popupError.length == 0) {
                        $popup.append('<p class="error">'+response.message+'</p>');
                    } else {
                        $popupError.text(response.message);
                    }

                    loaderOff();
                }
            });
        })

        //Открытие всплывающего окна создания занятия
        .on('click', '.add_lesson', function() {
            let date = $(this).data('date');
            let lessonDate = new Date(date);
            let currentDate = new Date(getCurrentDate());

            if (lessonDate.valueOf() < currentDate.valueOf()) {
                return false;
            }

            let type = $(this).data('schedule_type');
            let classId = $(this).data('class_id');
            let areaId = $(this).data('area_id');
            getScheduleLessonPopup(classId, date, areaId, type);
        })

        //Сохранение данных занятия
        .on("click", ".popop_schedule_lesson_submit", function(e) {
            e.preventDefault();
            loaderOn();

            let Form = $('#createData');
            let clientId = Form.find('[name=clientId]').val();
            let teacherId = Form.find('select[name=teacherId]').val();
            let date = Form.find('input[name=insertDate]').val();
            let timeFrom = Form.find('input[name=timeFrom]').val();
            let timeTo = Form.find('input[name=timeTo]').val();
            let areaId = Form.find('input[name=areaId]').val();
            let lessonType = Form.find('input[name=lessonType]').val();
            let typeId = Form.find('select[name=typeId]').val();
            let dayName = Form.find('input[name=dayName]').val();
            let isCreateTask = $('input[name=is_create_task]');
            let classId = $('input[name=classId]').val();
            let isOnline = Form.find('input[name=isOnline]').is(':checked') ? 1 : 0;

            let lessonData = {
                scheduleType: lessonType,
                typeId: typeId,
                insertDate: date,
                clientId: clientId,
                teacherId: teacherId,
                areaId: areaId,
                classId: classId,
                timeFrom: timeFrom + ':00',
                timeTo: timeTo + ':00',
                isOnline: isOnline,
                dayName: dayName
            };

            let lessonSaveCallback = function (response) {
                if (response.status === true) {
                    notificationSuccess('Занятие сохранено');
                    closePopup();
                    addTask(isCreateTask, clientId, date, areaId);
                    refreshSchedule();
                } else {
                    notificationError(response.message);
                    loaderOff();
                }
            }

            //Проверка на принадлежность занятия рабочему времени преподавателя
            Schedule.isInTeacherTime({
                teacher_id: teacherId,
                day_name: dayName,
                time_from: timeFrom,
                time_to: timeTo
            }, function(response) {
                let
                    isConfirmed = true,
                    needConfirm = false;

                if (response.time == null) {
                    needConfirm = true;
                }
                if (needConfirm === true) {
                    isConfirmed = confirm('Занятие выходит за рамки графика работы преподавателя, хотите продолжить?');
                } else {
                    isConfirmed = true;
                }

                if (isConfirmed === true) {
                    //Проверка преподавателя на отсутствие
                    Schedule.checkAbsentPeriod({
                        userId: teacherId,
                        date: date,
                        timeFrom: timeFrom,
                        timeTo: timeTo
                    }, function (response) {
                        if (response.isset == true) {
                            alert('В указанное время преподаватель отсутствует');
                            loaderOff();
                        } else {
                            //Если это индивидуальное занятие
                            if (typeId == 1) {
                                Schedule.checkAbsentPeriod({userId: clientId, date: date}, function (response) {
                                    //Если есть существующий период отсутсвия
                                    if (response.isset == true) {
                                        //Постановка в основной график
                                        if (lessonType == 1) {
                                            if (confirm('В данное время у клиента существует активный период отсутсвия с ' + response.period.dateFrom[1] + ' по ' + response.period.dateTo[1] + '. Хотите продолжить?')) {
                                                Schedule.saveLesson(lessonData, lessonSaveCallback);
                                            } else {
                                                loaderOff();
                                            }
                                        } else { //Постановка в актуальный график
                                            alert('Постановка клиента в расписание на данную дату невозможна, так как у него имеется активный'
                                                + ' период отсутствия с ' + response.period.dateFrom[1] + ' по ' + response.period.dateTo[1]);
                                            loaderOff();
                                        }
                                    } else {
                                        Schedule.saveLesson(lessonData, lessonSaveCallback);
                                    }
                                });
                            } else {
                                if (typeId == 3){
                                    checkPropertyValue('teacher_stop_list','User',teacherId, function(data) {
                                        if (data.value.value != 0 ) {
                                            alert('Преподаватель в стоп листе, постановка консультации невозможна!!!');
                                            loaderOff();
                                        } else {
                                            Schedule.saveLesson(lessonData, lessonSaveCallback);
                                        }
                                    });
                                } else {
                                    //Сделал сразу заготовку для добавления задачи группе
                                    Schedule.saveLesson(lessonData, lessonSaveCallback);
                                }
                            }
                        }
                    });
                } else {
                    loaderOff();
                }
            });
        })

        //Удаление занятия из основного графика
        .on('click', '.schedule_delete_main', function(e) {
            e.preventDefault();
            loaderOn();
            let lessonid = $(this).data('id');
            let deletedate = $(this).data('date');
            markDeleted(lessonid, deletedate, refreshSchedule);
        })

        //Выставка отметки об разовом отсутствии занятия
        .on('click', '.schedule_today_absent', function(e) {
            e.preventDefault();
            loaderOn();
            let lessonId = $(this).parent().parent().data('id');
            let clientId = $(this).parent().parent().data('client');
            let date = $(this).parent().parent().data('date');
            let pageType = $(this).data('page_type');
            markAbsent(lessonId, clientId, date, function(response) {
                if (response.error !== undefined) {
                    notificationError(response.message);
                    loaderOff();
                } else {
                    refreshSchedule(pageType);
                }
            });
        })

        //Открытие всплывающего окна для редактирования времени проведения занятия
        .on('click', '.schedule_update_time', function(e) {
            e.preventDefault();
            let lessonid = $(this).parent().parent().data('id');
            let date = $(this).parent().parent().data('date');
            getScheduleChangeTimePopup(lessonid, date);
        })

        //Сохранение изменения времени проведения занятия
        .on('click', '.popop_schedule_time_submit', function(e) {
            e.preventDefault();
            loaderOn();
            let timeFrom = $('input[name=timeFrom]').val();
            let timeTo = $('input[name=timeTo]').val();
            let lessonId = $('input[name=lesson_id]').val();
            let date = $('input[name=date]').val();
            saveScheduleChangeTimePopup(lessonId, date, timeFrom, timeTo, refreshSchedule);
        })

        /**
         * Подгрузка элементов выпадающего списка в зависимости от типа занятия:
         * индивидуальное, групповое или консультация
         */
        .on('change', 'select[name=typeId]', function() {
            loaderOn();
            let type = $(this).val();
            let select = $('.clients');

            if (type != 0) {
                select.show();
            } else {
                select.hide();
            }

            let rememberRow = $('#createData').find('.remember');

            if (type == 3) {
                let select = $('select[name=clientId]');
                let selectBlock = select.parent();
                select.remove();
                selectBlock.append("<input type='number' name='clientId' class='form-control' placeholder='Номер лида' />");
                $.each(rememberRow, function(index, value){
                    $(value).hide();
                });
                loaderOff();
            } else {
                let input = $('input[name=clientId]');
                let clientsList = $('#createData').find('select[name=clientId]');
                if (input.length > 0) {
                    var inputBlock = input.parent();
                    inputBlock.append("<select name='clientId' class='form-control valid' ></select>");
                    clientsList = inputBlock.find('select');
                    input.remove();
                }

                $.each(rememberRow, function(index, value){
                    $(value).show();
                });

                var callback = function(users) {
                    let clientsList = $('#createData').find('select[name=clientId]');
                    clientsList.append('<option value="0"> ... </option>');
                    $.each(users, function(key, user) {
                        if (user.active == 1 || user.active == undefined) {
                            clientsList.append('<option value="'+user.id+'">'+user.surname + ' ' + user.name +'</option>');
                        }
                    });
                    loaderOff();
                };

                clientsList.empty();
                if (type == 1 || type == 5) {
                    if ($('select[name=teacherId]').val() > 0) {
                        User.getListByTeacherId($('select[name=teacherId]').val(), callback);
                    } else {
                        User.getList({
                            select: ['id', 'surname', 'name'],
                            active: true,
                            groups: [5],
                            order: { surname: 'ASC' }
                        }, callback);
                    }
                } else {
                    let typeId = type == 2 ? 1 : 2,
                        dateFrom = $('input[name=insertDate]').val();
                    Group.getList({active: true, type: typeId, date_from: dateFrom}, function (groups) {
                        $.each(groups, function (key, group) {
                            let option = '<option value="'+group.id+'">'+group.title;
                            if (typeId === 2) {
                                option += ' (' + group.refactored_date + ' ' + group.refactored_time_start + ')';
                            }
                            option += '</option>';

                            clientsList.append(option);
                        });
                        loaderOff();
                    });
                }
            }
        })

        /**
         * Формирование списка клиентов по принадлежности к преподавателю
         */
        .on('change', 'select[name=teacherId]', function(e){
            var lessonTypeId = $('select[name=typeId]').val();
            if (lessonTypeId == 1 || lessonTypeId == 5) {
                let
                    clientsList = $('select[name=clientId]'),
                    selectedClient = clientsList.val(),
                    selectedTeacher = $('select[name=teacherId]').val();

                if (selectedClient > 0) {
                    return false;
                }

                loaderOn();

                if (selectedTeacher == 0) {
                    clientsList.empty();
                    User.getList({
                        select: ['id', 'surname', 'name'],
                        active: true,
                        groups: [5],
                        order: {surname: 'ASC'}
                    }, function (users) {
                        clientsList.append('<option value="0"> ... </option>');
                        $.each(users, function (key, user) {
                            clientsList.append('<option value="' + user.id + '">' + user.surname + ' ' + user.name + '</option>');
                        });
                        loaderOff();
                    });
                } else {
                    clientsList.empty();
                    User.getListByTeacherId(selectedTeacher, function (users) {
                        clientsList.append('<option value="0"> ... </option>');
                        $.each(users, function (key, user) {
                            if (user.active == 1) {
                                clientsList.append('<option value="' + user.id + '">' + user.surname + ' ' + user.name + '</option>');
                            }
                        });
                        loaderOff();
                    });
                }
            }
        })

        /**
         * Отправка отчета преподавателем о проведении занятия
         */
        .on('click', '.send_report', function(e) {
            e.preventDefault();
            loaderOn();
            let tr = $(this).parent().parent();
            let lessonId = tr.find('input[name=lessonId]').val();
            let date = tr.find('input[name=date]').val();
            let typeId = tr.find('input[name=typeId]').val();
            let attendance = tr.find('input[type=checkbox]');
            let note = tr.find('input[name=note]');
            let fileInput = tr.find('input[type=file]');

            let ajaxData = {
                action: 'teacherReport',
                date: date,
                lessonId: lessonId
            };

            if (typeId == 2 || typeId == 4) {
                $.each(attendance, function(key, input) {
                    let name = $(input).attr('name');
                    if (name != 'group') {
                        ajaxData[name] = Number($(input).is(':checked'));
                    } else {
                        ajaxData['attendance'] = Number($(input).is(':checked'));
                    }
                });
            } else {
                ajaxData['attendance'] = Number($(attendance[0]).is(':checked'));
            }

            //Отправка данных о проведенном занятии
            $.ajax({
                type: 'GET',
                url: root + '/schedule',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (checkResponseStatus(response)) {
                        //Создание комментария лиду
                        if (typeId == 3 && note.lendth != 0 && note.val() != '') {
                            Lids.saveComment(0, note.data('lidid'), note.val(), function(response){
                                if (checkResponseStatus(response)) {
                                    if (fileInput.val() != '') {
                                        FileManager.upload(0, 1, fileInput, 'Comment', response.id, function(file) {
                                            refreshSchedule();
                                        });
                                    } else {
                                        refreshSchedule();
                                    }
                                }
                            });
                        } else {
                            refreshSchedule();
                        }
                    }
                },
                error: function() {
                    notificationError('При отправке отчета произошла ошибка. Обновите страницу и попробуйте снова');
                    loaderOff();
                }
            });
        })

        //Удаление отчета о проведении занятия
        .on('click', '.delete_report', function(e) {
            e.preventDefault();
            loaderOn();
            var tr = $(this).parent().parent();
            var lessonId = tr.find('input[name=lessonId]').val();
            var date = tr.find('input[name=date]').val();

            $.ajax({
                type: 'GET',
                url: root + '/schedule',
                data: {
                    action: 'deleteReport',
                    lesson_id: lessonId,
                    date: date
                },
                success: function(response) {
                    if (response != '') {
                        alert(response);
                    }
                    refreshSchedule();
                }
            });
        })
        /**
         * Сохранение данных задачи из раздела расписания
         */
        .on('click', '.popop_schedule_task_submit', function(e) {
            e.preventDefault();
            loaderOn();
            var form = $('#createData');

            if (form.valid()) {
                var formData = form.serialize();
                saveScheduleTask(formData, loaderOff);
            } else {
                loaderOff();
            }
        })

        //Сохранение выплаты преподавателю
        .on('click', '.add_teacher_payment', function(e) {
            e.preventDefault();
            loaderOn();
            var date = $('.teacher_payments').find('input[name=date]').val();
            var summ = $('.teacher_payments').find('input[name=summ]').val();
            var user = $('.teacher_payments').find('input[name=userid]').val();
            var description = $('.teacher_payments').find('input[name=description]').val();
            saveTeacherPayment(user, summ, date, description, refreshSchedule);
        })

        //Указание месяца / года клиентом и подгрузка расписания за выбранный период
        .on('change', '.client_schedule', function(){
            loaderOn();

            var month = $('#month').val();
            var year = $('#year').val();
            var userid = $('#userid').val();

            $.ajax({
                type: 'GET',
                url: '',
                data: {
                    ajax: 1,
                    year: year,
                    month: month,
                    userid: userid
                },
                success: function(response) {
                    $('.users').html(response);
                    loaderOff();
                }
            });
        })

        //Открытие всплывающего окна редактирования филиала
        .on('click', '.schedule_area_edit', function(e) {
            e.preventDefault();
            var areaId = $(this).data('area_id');
            getScheduleAreaPopup(areaId);
        })

        //Сохранение данных филиала
        .on('click', '.popop_schedule_area_submit', function(e) {
            e.preventDefault();
            loaderOn();
            saveData('Main', function(response){refreshAreasTable();});
        })

        //Изменение активности филлиала
        .on('click', 'input[name=schedule_area_active]', function() {
            var areaId = $(this).data('area_id');
            var value = $(this).prop('checked');
            updateActive('Schedule_Area', areaId, value, loaderOff);
        })

        //Удаление филиала
        .on('click', '.schedule_area_delete', function(e) {
            e.preventDefault();
            loaderOn();
            var areaId = $(this).data('area_id');
            deleteItem('Schedule_Area', areaId, refreshAreasTable);
        })

        //Открытие или скрытие формы создания
        .on('click', '.new-teacher-time', function(e) {
            e.preventDefault();
            let formBlock = $(this).parent().parent().find('.new-time-form');
            if (formBlock.css('display') === 'none') {
                formBlock.show();
                $(this).text('-');
            } else {
                formBlock.hide();
                $(this).text('+');
            }
        })

        //Обработчик события сохранения нового рабочего времени преподавател
        .on('click', '.teacher-time-save', function(e) {
            e.preventDefault();
            let form = $(this).parent().parent();
            let data = {};
            data.teacher_id = form.find('input[name=teacher_id]').val();
            data.time_from = form.find('input[name=time_from]').val();
            data.time_to = form.find('input[name=time_to]').val();
            data.day_name = form.find('input[name=day_name]').val();

            loaderOn();
            Schedule.saveTeacherTime(data, function(response) {
                if (checkResponseStatus(response)) {
                    refreshSchedule();
                }
                loaderOff();
            });
        })

        .on('click', '.user-schedule-btn', function(e) {
            e.preventDefault();
            let scheduleSection = $('.user-schedule');
            if (scheduleSection.css('display') == 'none') {
                scheduleSection.show('slow');
            } else {
                scheduleSection.hide('slow');
            }
        })
        .on('change', '#clientLessonPopupTeacherId', function() {
            loaderOn();
            Schedule.getTeacherSchedule($(this).val(), function (response) {
                let $row = $('#teacherScheduleRow');
                $row.empty()
                $.each(response.schedule, function (key, day) {
                    let rowDay = day.dayName + ':';
                    $.each(day.times, function (key, time) {
                        rowDay += ' ' + time.refactoredTimeFrom + ' - ' + time.refactoredTimeTo + ';';
                    });
                    $row.append('<div class="col-md-6">'+rowDay+'</div>');
                });
                loaderOff();
            });
        });


    //Формирование текущей даты для календаря
    var today = new Date();
    var day =   today.getDate();
    var month = today.getMonth() + 1;
    var year =  today.getFullYear();

    $(".day_name").text(days[today.getDay()]);

    if (day < 10)    day = '0' + day;
    if (month < 10)  month = '0' + month;

    var result = year + '-' + month + '-' + day;
    $('.schedule_calendar').val(result);
});


// Создание задачи с напоминанием
function addTask(isCreateTask,clientId,date,areaId) {
    if (isCreateTask.is(':checked')) {
        $.ajax({
            type: 'GET',
            url: '',
            data: {
                action: 'create_schedule_task',
                date: date,
                clientId: clientId,
                areaId: areaId
            }
        });
    }
    isCreateTask.remove();
}

function refreshAreasTable() {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'getSchedule',
        },
        success: function(responSe) {
            $('.page').html(responSe);
            loaderOff();
        }
    });
}


function saveTeacherPayment(user, summ, date, description, func) {
    $.ajax({
        type: 'GET',
        url: root + '/admin?menuTab=Main&menuAction=updateAction&ajax=1',
        // async: false,
        data: {
            id: '',
            modelName: 'Payment',
            user: user,
            value: summ,
            type: 3,
            datetime: date,
            description: description
        },
        success: function(responSe) {
            if (responSe != '0') alert('Ошибка: ' + responce);
            closePopup();
            func();
        }
    });
}


function getScheduleAreaPopup(areaId) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'getScheduleAreaPopup',
            areaId: areaId
        },
        success: function(response){
            showPopup(response);
        }
    });
}


function saveScheduleTask(formData, func) {
    formData += '&action=save_task';

    $.ajax({
        type: 'GET',
        url: '',
        data: formData,
        success: function(response) {
            if(response != '0') {
                alert(response);
            } else {
                notificationSuccess('Ваше обращение доставлено менеджерам');
            }
            closePopup();
            func();
            loaderOff();
        }
    });
}

function refreshSchedule(pageType) {
    if (pageType == 'client') {
        $.ajax({
            type: 'GET',
            url: window.location.href + '&ajax=1',
            dataType: 'html',
            success: function(response) {
                $('.page').html(response);
                loaderOff();
            }
        });
    } else {
        $('.schedule_calendar').trigger('change');
    }
}

function getSchedule(userId, date, func) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'getSchedule',
            userid: userId,
            date: date,
        },
        success: function(response) {
            $('.schedule').html(response);
            if (typeof func === 'function') {
                func();
            }
            loaderOff();
        }
    });
}


/**
 * Открытие всплывающего окна создания/редактирования периода отсутствия
 *
 * @param objectId
 * @param typeId
 * @param date
 * @param id
 */
function getScheduleAbsentPopup(objectId, typeId, date, id) {
    $.ajax({
        type: 'GET',
        url: root + '/schedule',
        // async: false,
        data: {
            action: 'getScheduleAbsentPopup',
            objectId: objectId,
            typeId: typeId,
            date: date,
            id: id
        },
        success: function(response) {
            showPopup(response);
        }
    });
}


/**
 * Удаление периода отсутствия клиента
 *
 * @param id
 * @param callback
 */
function deleteScheduleAbsent(id, callback) {
    loaderOn();
    $.ajax({
        type: 'POST',
        url: root + '/api/schedule/index.php',
        dataType: 'json',
        data: {
            action: 'deleteScheduleAbsent',
            id: id
        },
        success: function(response) {
            if (typeof callback == 'function') {
                callback(response);
            }
        },
        error: function() {
            notificationError('При удалении периода отсутсвия произошла ошибка');
            loaderOff();
        }
    });
}


/**
 * Колбэк для удаления периода отсутствия клиента
 *
 * @param response
 */
function deleteAbsentClientCallback(response) {
    notificationSuccess('Период отсутствия ' + response.fio + ' с ' + response.dateFrom + ' по '
        + response.dateTo + ' успешно удален');
    $('.row[data-period-id='+response.id+']').remove();
    let absentRow = $('#absent-row');
    if (absentRow.find('.periods').find('div').length == 0) {
        absentRow.remove();
    }
    loaderOff();
}


function getScheduleLessonPopup(classId, date, areaId, lessonType) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'getScheduleLessonPopup',
            classId: classId,
            date: date,
            lessonType: lessonType,
            areaId: areaId
        },
        success: function(response) {
            showPopup(response);
            var clientsSelect = $('select[name=typeId]');
            clientsSelect.val('1');
            clientsSelect.trigger('change');
        }
    });
}

function markDeleted(lessonId, deleteDate, func) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'markDeleted',
            lessonid: lessonId,
            deletedate: deleteDate
        },
        success: function(response) {
            func();
        }
    });
}

/**
 * Отмена занятия
 *
 * @param lessonId
 * @param clientId
 * @param date
 * @param func
 */
function markAbsent(lessonId, clientId, date, func) {
    $.ajax({
        type: 'POST',
        url: root + '/api/schedule/index.php',
        dataType: 'json',
        data: {
            action: 'markAbsent',
            lessonId: lessonId,
            clientId: clientId,
            date: date
        },
        success: function(response) {
            func(response);
        }
    });
}


function getScheduleChangeTimePopup(lessonId, date) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'getScheduleChangeTimePopup',
            id: lessonId,
            date: date
        },
        success: function(response) {
            showPopup(response)
        }
    });
}


function saveScheduleChangeTimePopup(lessonId, date, timeFrom, timeTo, func) {
    $.ajax({
        type: 'GET',
        url: '',
        data: {
            action: 'saveScheduleChangeTimePopup',
            lesson_id: lessonId,
            date: date,
            time_from: timeFrom,
            time_to: timeTo
        },
        success: function(response){
            if (response != '') {
                alert(response);
            }
            closePopup();
            func();
        }
    });
}


function addAbsentTask(dateTo, clientId) {
    $.ajax({
        type: 'GET',
        url: root + '/schedule',
        data: {
            action: 'addAbsentTask',
            date_to: dateTo,
            client_id: clientId
        },
        success: function(response) {
            if (response != '') {
                alert(responce);
            }
        }
    });
}


/**
 *
 * @param areaId
 * @param classId
 * @param td
 * @returns void
 */
function scheduleEditClassName(areaId, classId, td) {
    if ($(td).find('.prevName').length > 0) {
        return;
    }

    if (!confirm('Вы хотите переименовать класс?')) {
        return;
    }

    var prevName = $(td).text();
    $(td).empty();

    var hiddenSpan = '<span class="prevName" style="display: none">'+prevName+'</span>';
    var editField = '<input class="form-control" id="newClassName" autofocus value="'+prevName+'" ' +
        'onblur="scheduleOnblurClassName('+areaId+', '+classId+', this.value)" />';

    $(td).append(hiddenSpan);
    $(td).append(editField);
}


function scheduleSaveClassName(areaId, classId, newName, callBack) {
    $.ajax({
        type: 'GET',
        url: root + '/schedule',
        dataType: 'json',
        data: {
            action: 'saveClassName',
            areaId: areaId,
            classId: classId,
            newName: newName
        },
        success: function (response) {
            if (typeof callBack === 'function') {
                callBack(response);
            }
        }
    });
}


function scheduleOnblurClassName(areaId, classId, newValue) {
    var editField = $('#newClassName');
    var td = editField.parent();
    var prevName = td.find('.prevName');

    if (!confirm('Сохранить изменения?')) {
        td.text(prevName.text());
        editField.remove();
        prevName.remove();
    } else {
        loaderOn();
        scheduleSaveClassName(areaId, classId, newValue, function(response) {
            td.empty();
            td.text(newValue);
            loaderOff();
        });
    }
}


/**
 * @param response
 */
function removeTeacherTimeCallback(response) {
    if (checkResponseStatus(response)) {
        $('.teacher-time-' + response.time.id).remove();
        notificationSuccess('Рабочее время преподавателя ' + response.teacher.surname + ' ' + response.teacher.name
            + ' с ' + response.time.refactoredTimeFrom + ' по ' + response.time.refactoredTimeTo + ' успешно удалено');
        loaderOff();
    }
}

function addNewStudentToTeacher(valueId) {
    var popupData = $(
        '<div align="center" class="row popup-row-block" id="accessGroupAssignments">' +
        '<div class="col-lg-12">' +
        '<h4>Добавить нового ученика</h4>' +
        '</div>' +
        '<div  class="col-lg-12 col-md-12 col-sm-12 col-xs-12">' +
        '<div class="row">' +
        '<div class="col-md-8">' +
        '<input type="text" id="mainUserQuery" class="form-control" placeholder="Фамилия">' +
        '</div>' +
        '<div class="col-md-3">' +
        '<a  class="btn btn-blue" onclick="' +
        'User.getList({' +
        'filter: {surname: $(\'#mainUserQuery\').val()},' +
        'active: 1,' +
        'select: [\'id\', \'surname\', \'name\', \'group_id\'],' +
        'groups: [5],' +
        'order: {group_id: \'ASC\', surname: \'ASC\'}' +
        '}, function(users){ ' +
        'var mainUserList = $(\'#mainUserList\'); mainUserList.empty();' +
        '$.each(users, function(key, user){' +
        'var option = \'<option value=\'+user.id+\'>\'+user.surname+ \' \' + user.name + \'</option>\';' +
        'mainUserList.append(option);' +
        '});' +
        '})' +
        '">Поиск</a>' +
        '</div>' +
        '</div>' +
        '<div class="row">' +
        '<select class="form-control" id="mainUserList" size="7"></select>' +
        '</div>' +
        '<div class="row text-center">' +
        '<a class="btn btn-large btn-green" ' +
        'onclick="User.appendClientToTeacher('+valueId+', $(\'#mainUserList\').val(), addTeachersStudentCallback)">Добавить</a>');

    //Формирование общего списка пользователей
    var mainUserList = popupData.find('#mainUserList');
    User.getList(
        {
            active: 1,
            select: ['id', 'surname', 'name', 'group_id'],
            groups: [5],
            order: {
                group_id: 'ASC',
                surname: 'ASC'
            }
        },
        function(response) {
            $.each(response, function(key, user){
                mainUserList.append('<option value="'+user.id+'">' + user.surname + ' ' + user.name + '</option>');
            });
        }
    );

    showPopup(popupData);
    return true;
}


function makeClientLessonPopup(clientId) {
    loaderOn();
    $.ajax({
        type: 'GET',
        url: root + '/balance',
        dataType: 'html',
        data: {
            action: 'makeClientLessonPopup',
            clientId: clientId
        },
        success: function(response) {
            showPopup(response);
            $('#clientLessonPopupTeacherId').trigger('change');
            loaderOff();
        }
    });
}


function teacherNearestFreeTimeCallback(response) {
    loaderOn();
    if(!checkResponseStatus(response)) {
        loaderOff();
        return false;
    }

    let $teacherTimeBlock = $('.teacherTime');
    let $saveBtnRow = $('.saveBtnRow');

    if (response.length == 0) {
        $teacherTimeBlock.html('<p>На данную дату не удалось подобрать свободное время</p>');
        $teacherTimeBlock.append('<p>Так как не удалось подобрать время занятия в автоматическом режиме - свяжитесь с менеджерами для получения более детальной информации</p>');
        $saveBtnRow.hide();
    } else {
        $teacherTimeBlock.html('<p>Возможное время для постановки в график:</p>');
        let $teacherTimeUl = $('<ul></ul>');
        $.each(response, function(key, time) {
            $teacherTimeUl.append('<li>' +
                '<input type="radio" name="time" id="time_'+time.timeFrom+'" value="'+time.timeFrom+' '+time.timeTo+'">' +
                '<label for="time_'+time.timeFrom+'">'+time.refactoredTimeFrom+' '+time.refactoredTimeTo+'</label></li>');
        });
        $teacherTimeBlock.append($teacherTimeUl);
        $teacherTimeBlock.append('<p>Для подбора другого времени занятия необходимо связаться с менеджерами</p>');
        $saveBtnRow.show();
    }

    loaderOff();
}


function saveClientLesson() {
    let
        $popup = $('.popup'),
        time = $popup.find('input[name=time]:checked').val(),
        data = {
            action: 'saveLesson',
            typeId: 1,
            scheduleType: 2,
            insertDate: $popup.find('input[name=date]').val(),
            clientId: $popup.find('input[name=clientId]').val(),
            teacherId: $popup.find('select[name=teacherId]').val(),
            areaId: $popup.find('input[name=areaId]').val(),
            timeFrom: time.split(' ')[0],
            timeTo: time.split(' ')[1]
        };

    Schedule.checkAbsentPeriod({
        userId: data.clientId,
        date: data.insertDate
    }, function(response) {
        if (response.isset === false) {
            $.ajax({
                type: 'POST',
                url: root + '/api/schedule/index.php',
                dataType: 'json',
                data: data,
                success: function(response) {
                    closePopup();
                    if(response.message !== undefined) {
                        notificationError(response.message);
                    } else {
                        notificationSuccess('Вы успешно были поставлены в график');
                        refreshSchedule();
                    }
                }
            });
        } else {
            notificationError('Невозможно поставить занятие в график при активном периоде отсутствия');
        }
    });
}

function getTeacherReportsStatistic(teacherId, dateFrom, dateTo) {
    loaderOn();
    Schedule.getReportsStatistic({
        teacher_id: teacherId,
        date_from: dateFrom,
        date_to: dateTo
    }, function(response) {
        let $table = $('<table class="table"></table>');
        $table.append('<tr><th>Тип занятия</th><th>Прис./отс.</th><th>Начислено</th></tr>');
        $.each(response, function(key, data) {
            let $tr = $('<tr></tr>');
            $tr.append('<td>'+data.title+'</td>');
            $tr.append('<td>'+data.count_attendance+' / '+data.count_absence+'</td>');
            $tr.append('<td>'+data.teacher_rate+'</td>');
            $table.append($tr);
        });
        showPopup($table);
        loaderOff();
    });
}