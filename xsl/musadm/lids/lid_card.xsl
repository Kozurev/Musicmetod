<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="lid">
        <xsl:variable name="statusId" select="status_id" />

        <div class="item {//lid_status[id = $statusId]/item_class} lid_{id}">
            <div class="item-inner">
                <div class="row">
                    <div class="col-sm-3 col-xs-12">
                        <h3 class="title">
                            <span class="id"><xsl:value-of select="id" /> <xsl:text> </xsl:text></span>
                            <span class="surname"><xsl:value-of select="surname" /><xsl:text> </xsl:text></span>
                            <span class="name"><xsl:value-of select="name" /><xsl:text> </xsl:text></span>
                            <!--<xsl:value-of select="patronimyc" /><xsl:text> </xsl:text>-->
                        </h3>

                        <p class="intro">
                            <xsl:if test="number = ''">
                                <xsl:attribute name="style">display:none</xsl:attribute>
                            </xsl:if>
                            <span class="number"><xsl:value-of select="number" /></span>
                        </p>

                        <p class="intro">
                            <xsl:if test="vk = ''">
                                <xsl:attribute name="style">display:none</xsl:attribute>
                            </xsl:if>
                            <span>ВК: </span><span class="vk"><xsl:value-of select="vk" /></span>
                        </p>

                        <xsl:if test="/root/access_lid_edit = 1">
                            <a class="action edit" onclick="makeLidPopup({id})" title="Редактировать лида"><input type="hidden" value="KOCTb|J|b" /></a>
                        </xsl:if>
                        <xsl:if test="/root/access_lid_comment = 1">
                            <a class="action comment" title="Добавить комментарий" onclick="makeLidCommentPopup(0, {id}, saveLidCommentCallback)"><input type="hidden" value="KOCTb|J|b" /></a>
                        </xsl:if>
                        <a class="action add_user" title="Создать пользователя" onclick="makeClientFromLidPopup({id})"><input type="hidden" value="KOCTb|J|b" /></a>
                        <xsl:if test="/root/access_lid_edit = 1">
                            <a class="action calendar"  onclick="getLidStatisticPopup({id})" title="События лида"><input type="hidden" value="KOCTb|J|b" /></a>
                        </xsl:if>
                        <xsl:if test="//current_user/email != '' and //my_calls_token != ''">
                            <a class="action phone" onclick="MyCalls.makeCall({//current_user/id}, '{number}', checkResponseStatus)" title="Совершить звонок"><input type="hidden" value="KOCTb|J|b" /></a>
                        </xsl:if>
                        <xsl:if test="/root/access_lid_edit = 1">
                            <a class="action settings lid_group_setting" data-id="{id}" title="Добавить в группу">
                                <input type="hidden" value="KOCTb|J|b" />
                            </a>
                        </xsl:if>
                        <input type="date" class="form-control date_inp lid_date" onchange="Lids.changeDate({id}, this.value)">
                            <xsl:attribute name="value"><xsl:value-of select="control_date" /></xsl:attribute>
                            <xsl:if test="/root/access_lid_edit = 0">
                                <xsl:attribute name="disabled">disabled</xsl:attribute>
                            </xsl:if>
                        </input>

                        <select name="status" class="form-control lid_status" onchange="Lids.changeStatus({id}, this.value, changeLidStatusCallback)">
                            <xsl:if test="/root/access_lid_edit = 0">
                                <xsl:attribute name="disabled">disabled</xsl:attribute>
                            </xsl:if>

                            <!--<option value="0"> ... </option>-->
                            <xsl:for-each select="/root/lid_status">
                                <xsl:variable name="id" select="id" />
                                <option value="{$id}">
                                    <xsl:if test="$id = $statusId">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="title" />
                                </option>
                            </xsl:for-each>
                        </select>

                        <select class="form-control lid-area" onchange="Lids.changeArea({id}, this.value)">
                            <xsl:if test="/root/access_lid_edit = 0">
                                <xsl:attribute name="disabled">disabled</xsl:attribute>
                            </xsl:if>
                            <option value="0"> ... </option>
                            <xsl:variable name="areaId" select="area_id" />
                            <xsl:for-each select="//schedule_area">
                                <option value="{id}">
                                    <xsl:if test="id = $areaId">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="title" />
                                </option>
                            </xsl:for-each>
                        </select>

                        <xsl:variable name="priorityId" select="priority_id" />
                        <select class="form-control lid_priority" onchange="Lids.changePriority({id}, this.value)">
                            <xsl:for-each select="//lid_priority" >
                                <option value="{id}">
                                    <xsl:if test="id = $priorityId">
                                        <xsl:attribute name="selected">selected</xsl:attribute>
                                    </xsl:if>
                                    <xsl:value-of select="title" />
                                </option>
                            </xsl:for-each>
                        </select>

                        <p class="intro">
                            <xsl:if test="property_value[property_id = 54]/value_id = 0">
                                <xsl:attribute name="style">display:none</xsl:attribute>
                            </xsl:if>
                            <xsl:variable name="markerId" select="property_value[property_id = 54]/value_id" />
                            <span>Маркер: </span><span class="marker"><xsl:value-of select="//property_list_values[id=$markerId]/value" /></span>
                        </p>

                        <xsl:variable name="source">
                            <xsl:choose>
                                <xsl:when test="property_value[property_id = 50]/value_id > 0">
                                    <xsl:variable name="sourceId" select="property_value[property_id = 50]/value_id" />
                                    <xsl:value-of select="//property_list_values[id=$sourceId]/value" />
                                </xsl:when>
                                <xsl:otherwise>
                                    <xsl:value-of select="source" />
                                </xsl:otherwise>
                            </xsl:choose>
                        </xsl:variable>

                        <p class="intro">
                            <xsl:if test="$source = ''">
                                <xsl:attribute name="style">display:none</xsl:attribute>
                            </xsl:if>
                            <span>Источник: </span><span  class="source"><xsl:value-of select="$source" /></span>
                        </p>
                    </div>
                    <div class="col-sm-9 col-xs-12 comments-column">
                        <div class="comments">
                            <input type="hidden" value="KOCTb|J|b" />
                            <xsl:for-each select="comments/comment[text != '']">
                                <div class="block">
                                    <div class="comment_header">
                                        <div class="author">
                                            <xsl:value-of select="author_fullname" />
                                        </div>
                                        <div class="date">
                                            <xsl:value-of select="refactoredDatetime" />
                                        </div>
                                    </div>

                                    <div class="comment_body">
                                        <xsl:value-of select="text" />
                                        <xsl:if test="count(file) &gt; 0">
                                            <hr/>
                                            <div class="comment_files">
                                                <xsl:for-each select="file">
                                                    <a target="_blank" href="{link}"><xsl:value-of select="real_name" /></a><br/>
                                                </xsl:for-each>
                                            </div>
                                        </xsl:if>
                                    </div>
                                </div>
                            </xsl:for-each>
                        </div>
                    </div>
                </div>
            </div><!--//item-inner-->
        </div><!--//item-->
    </xsl:template>
</xsl:stylesheet>