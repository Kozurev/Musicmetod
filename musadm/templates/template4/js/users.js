$(function(){
    $("body")
    //Открытие всплывающего окна редактирования пользователя
        .on("click", ".user_edit", function(e){
            e.preventDefault();
            var userid = $(this).data("userid");
            var usergroupid = $(this).data("usergroup");

            if(usergroupid == 5)
                getClientPopup(userid);
            else
                getTeacherPopup(userid);
        })
        .on("click", ".user_create", function(e){
            e.preventDefault();
            var userid = 0;
            var usergroupid = $(this).data("usergroup");

            if(usergroupid == 5)
                getClientPopup(userid);
            else
                getTeacherPopup(userid);
        })
        //Сохранение данных
        .on("click", ".popop_user_submit", function(e){
            e.preventDefault();
            loaderOn();
            userSave(refreshUserTable);
            //refreshUserTable();
        })
        //Добавление пользователя в архив
        .on("click", ".user_archive", function(){
            var agree = confirm("Перенести пользователя в архив?");
            if(agree != true) return;
            var userid = $(this).data("userid");
            loaderOn();
            changeUserActive(userid, "false");
        })
        //"Разархивирование пользователя"
        .on("click", ".user_unarchive", function(){
            var agree = confirm("Убрать пользователя из архива?");
            if(agree != true) return;
            loaderOn();
            var userid = $(this).data("userid");
            changeUserActive(userid, "true");
        })
        //Удаление пользователя
        .on("click", ".user_delete", function(e){
            e.preventDefault();
            var userid = $(this).data("model_id");
            deleteItem("User", userid, "../admin?menuTab=Main&menuAction=deleteAction&ajax=1", refreshArchiveTable);
        })
        //Нажатие на кнопку закрытия высплывающего окна редактирования пользователя
        .on("click", ".popup_close", function(e){
            e.preventDefault();
            closePopup();
        })
        //Начисление платежа пользователю (форма)
        .on("click", ".add_payment", function(e){
            e.preventDefault();
            var userid = $(this).data("userid");
            getPaymentPopup(userid, "client");
        })
        //Сохранение платежа
        .on("click", ".popop_user_payment_submit", function(e){
            e.preventDefault();
            loaderOn();
            var form = $("#createData");
            if($(form).valid() == false)
            {
                loaderOff();
                return;
            }
            var userid = $(this).data("userid");
            var value = $(form).find("input[name=value]").val();
            var description = $(form).find("textarea[name=description]").val();
            var type = $(form).find("input[name=type]:checked").val();
            savePayment(userid, value, description, type, "client", refreshUserTable);
        })
        //Сохранение заметок клиента
        .on("blur", "#client_notes", function(){
            loaderOn();
            var note = $(this).val();
            var userid = $(this).data("userid");
            updateUserNote(userid, note, loaderOff);
        })
        //Сохранение логина клиента в личном кабинете
        .on("click", ".change_login_submit", function(e){
            e.preventDefault();
            loaderOn();
            userSave(loaderOff);
            //saveData("../admin?menuTab=User&menuAction=updateAction&ajax=1", loaderOff);
            $("input[name=pass1]").val('');
            $("input[name=pass2]").val('');
        })
        .on("click", ".balance_show", function(e){
            e.preventDefault();
            loaderOn();
            var date_from = $("input[name=date_from]").val();
            var date_to = $("input[name=date_to]").val();
            $.ajax({
                type: "GET",
                url: "",
                data: {
                    date_from: date_from,
                    date_to: date_to
                },
                success: function(responce){
                    $("body").empty();
                    $("body").html(responce);
                    loaderOff();
                }
            });
        });
});


function userSave(func) {
    var login = $("input[name=login]").val();
    var userid = $("input[name=id]").val();

    $.ajax({
        type: "GET",
        url: "client",
        data: {
            action: "checkLoginExists",
            login: login,
            userid: userid
        },
        success: function(responce){
            if(responce != "")
            {
                alert(responce);
                loaderOff();
            }
            else
            {
                if( $("#createData").valid() )
                    saveData("../admin?menuTab=User&menuAction=updateAction&ajax=1", func);
                else
                    loaderOff();
            }
        }
    });
}


function updateUserNote(userid, note, func) {
    $.ajax({
        type: "GET",
        url: "balance",
        data: {
            action: "updateNote",
            userid: userid,
            note: note
        },
        success: function(responce){
            func();
            if(responce != "")  alert(responce);
        }
    });
}


function getPaymentPopup(userid, url) {
    $.ajax({
        type: "GET",
        url: url,
        data: {
            action: "getPaymentPopup",
            userid: userid
        },
        success: function(responce) {
            showPopup(responce);
        }
    });
}


function refreshUserTable() {
    $.ajax({
        type: "GET",
        url: "",
        async: false,
        data: {
            action: "refreshTableUsers",
            //group: groupid
        },
        success: function(responce) {
            $(".users").empty();
            $(".users").append(responce);
            $("#sortingTable").tablesorter();
            loaderOff();
        }
    });
}


function refreshArchiveTable(func) {
    $.ajax({
        type: "GET",
        url: "archive",
        data: {
            action: "refreshTableArchive"
        },
        success: function(responce) {
            $(".page").empty();
            $(".page").append(responce);
            $("#sortingTable").tablesorter();
            if(func) func();
        }
    });
}


function changeUserActive(userid, status) {
    $.ajax({
        type: "GET",
        url: "../admin?menuTab=Main&menuAction=updateActive&ajax=1",
        data: {
            model_name: "User",
            model_id: userid,
            value: status
        },
        success: function(responce){
            var url;
            if(status == "false") url = "client";
            else url = "archive";

            refreshUserTable("clients", url, loaderOff);
        }
    });
}

function getClientPopup(userid) {
    $.ajax({
        type: "GET",
        url: "client",
        data: {
            action: "updateFormClient",
            userid: userid,
        },
        success: function(responce){
            showPopup(responce);
        }
    });
}

function getTeacherPopup(userid) {
    $.ajax({
        type: "GET",
        url: "teacher",
        data: {
            action: "updateFormTeacher",
            userid: userid,
        },
        success: function(responce){
            showPopup(responce);
        }
    });
}