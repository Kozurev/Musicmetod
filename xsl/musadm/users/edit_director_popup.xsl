<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="root">
        <!--<xsl:variable name="modelid" select="object_id" />-->
        <!--<xsl:variable name="modelname" select="model_name" />-->

        <script>
            $(function(){
                $("#createData").validate({
                    rules: {
                        surname:    {required: true, maxlength: 255},
                        name:       {required: true, maxlength: 255},
                        login:      {required: true, maxlength: 255},
                    },
                    messages: {
                        surname: {
                            required: "Это поле обязательноое к заполнению",
                            maxlength: "Длина значения не должна превышать 255 символов"
                        },
                        name: {
                            required: "Это поле обязательноое к заполнению",
                            maxlength: "Длина значения не должна превышать 255 символов"
                        },
                        login: {
                            required: "Это поле обязательноое к заполнению",
                            maxlength: "Длина значения не должна превышать 255 символов"
                        },
                    }
                });
            });
        </script>

        <form name="createData" id="createData" action=".">
            <div class="column">
                <span>Фамилия</span><span style="color:red" >*</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/surname}" name="surname" />
            </div>
            <hr/>
            <div class="column">
                <span>Имя</span><span style="color:red" >*</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/name}" name="name"  />
            </div>
            <hr/>
            <div class="column">
                <span>Отчество</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/patronymic}" name="patronymic"  />
            </div>
            <hr/>
            <div class="column">
                <span>Телефон</span>
            </div>
            <div class="column">
                <input class="form-control masked-phone" type="text" value="{user/phone_number}" name="phoneNumber" />
            </div>
            <hr/>
            <div class="column">
                <span>Email</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/email}" name="email" autocomplete="off" />
            </div>
            <hr/>
            <div class="column">
                <span>Логин</span><span style="color:red" >*</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/login}" name="login" autocomplete="off" />
            </div>
            <hr/>
            <div class="column">
                <span>Пароль</span>
            </div>
            <div class="column">
                <input class="form-control" type="password" value="" name="pass1" autocomplete="off" />
            </div>
            <hr/>
            <div class="column">
                <span>Повторите пароль</span>
            </div>
            <div class="column">
                <input class="form-control" type="password" value="" name="pass2" autocomplete="off" />
            </div>
            <hr/>
            <div class="column">
                <span>Город</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{property_value[property_id=29]/value}" name="property_29[]" />
            </div>
            <hr/>
            <div class="column">
                <span>Ссылка</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{property_value[property_id=33]/value}" name="property_33[]" />
            </div>
            <hr/>
            <div class="column">
                <span>Организация</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{property_value[property_id=30]/value}" name="property_30[]" />
            </div>
            <hr/>

            <input type="hidden" name="id" value="{user/id}" />
            <input type="hidden" name="groupId" value="6" />
            <input type="hidden" name="active" value="1" />
            <input type="hidden" name="modelName" value="User" />

            <script>
                $(".masked-phone").mask("+79999999999");
            </script>

            <button class="popop_user_submit btn btn-default">Сохранить</button>
        </form>
    </xsl:template>


</xsl:stylesheet>