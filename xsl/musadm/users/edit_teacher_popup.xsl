<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="root">
        <!--<xsl:variable name="modelid" select="object_id" />-->
        <!--<xsl:variable name="modelname" select="model_name" />-->

        <script>
            $(function(){
            $("#createData").validate({
            rules: {

            },
            messages: {

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
                <span>Телефон</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/phone_number}" name="phoneNumber" />
            </div>
            <hr/>
            <div class="column">
                <span>Логин</span><span style="color:red" >*</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="{user/login}" name="login" />
            </div>
            <hr/>
            <div class="column">
                <span>Пароль</span>
            </div>
            <div class="column">
                <input class="form-control" type="text" value="" name="password" />
            </div>
            <hr/>
            <div class="column">
                <span>Инструмент</span>
            </div>
            <div class="column">
                <select name="property_20[]" class="form-control">
                    <option value="0">...</option>
                    <xsl:call-template name="property_list">
                        <xsl:with-param name="property_id" select="20" />
                    </xsl:call-template>
                </select>
            </div>

            <input type="hidden" name="id" value="{user/id}" />
            <input type="hidden" name="modelName" value="User" />


            <button class="user_edit_submit btn btn-default">Сохранить</button>
        </form>
    </xsl:template>


    <xsl:template name="property_list">
        <xsl:param name="property_id" />

        <xsl:for-each select="property_list[property_id=$property_id]">
            <xsl:variable name="id" select="id" />
            <option value="{$id}">
                <xsl:if test="count(//property_value[id=$id]/value) != 0">
                    <xsl:attribute name="selected">selected</xsl:attribute>
                </xsl:if>
                <xsl:value-of select="value" />
            </option>
        </xsl:for-each>

    </xsl:template>


</xsl:stylesheet>