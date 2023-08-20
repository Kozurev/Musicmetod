<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="root">
        <h2 class="center">Сортировка лидов</h2>
        <xsl:variable name="selectedTeacherId" select="selectedTeacherId" />
        <xsl:variable name="selectedAreaId" select="selectedAreaId" />
        <xsl:if test="count(user) != 0">
            <div class="row">
                <div class="col-md-4">
                    <h4>Преподаватель:</h4>
                    <select class="form-control" id="lids_statistic_teacherId">
                        <option value="0"> ... </option>
                        <xsl:for-each select="user">
                            <!--<xsl:variable name="id" select="id" />-->
                            <option value="{id}">
                                <xsl:if test="id = $selectedTeacherId">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="surname" />
                                <xsl:text> </xsl:text>
                                <xsl:value-of select="name" />
                            </option>
                        </xsl:for-each>
                    </select>
                </div>
                <div class="col-md-4">
                    <h4>Филиал:</h4>
                    <select class="form-control" id="lids_statistic_areaId">
                        <option value="0"> ... </option>
                        <xsl:for-each select="schedule_area">
                            <!--<xsl:variable name="id" select="id" />-->
                            <option value="{id}">
                                <xsl:if test="id = $selectedAreaId">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:value-of select="title" />
                            </option>
                        </xsl:for-each>
                    </select>
                </div>
            </div>
        </xsl:if>

        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <h3 class="center">по дате созвона</h3>
            <table class="table table-bordered table-hover statistic_lids_table">
                <tr>
                    <th colspan="2">Лиды</th>
                </tr>
                <tr>
                    <td>Всего:</td>
                    <td><xsl:value-of select="total" /></td>
                </tr>
                <xsl:apply-templates select="status" />
            </table>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <h3 class="center">по расписанию</h3>

            <table class="table table-bordered table-hover statistic_lids_table">
                <tr>
                    <th colspan="2">Лиды</th>
                </tr>
                <tr>
                    <td>Всего:</td>
                    <td><xsl:value-of select="totalFromSchedule" /></td>
                </tr>
                <xsl:apply-templates select="statusSchedule" />
            </table>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
            <h3 class="center">по дате создания</h3>
            <table class="table table-bordered table-hover statistic_lids_table">
                <tr>
                    <th colspan="2">Лиды</th>
                </tr>
                <tr>
                    <td>Всего:</td>
                    <td><xsl:value-of select="totalFromDateControl" /></td>
                </tr>
                <xsl:apply-templates select="statusDateControl" />
            </table>
        </div>
    </xsl:template>


    <xsl:template match="status">
        <tr>
            <td><xsl:value-of select="title" /></td>
            <td>
                <xsl:value-of select="count" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="statusSchedule">
        <tr>
            <td><xsl:value-of select="title" /></td>
            <td>
                <xsl:value-of select="countSchedule" />
            </td>
        </tr>
    </xsl:template>

    <xsl:template match="statusDateControl">
        <tr>
            <td><xsl:value-of select="title" /></td>
            <td>
                <xsl:value-of select="countDateControl" />
            </td>
        </tr>
    </xsl:template>

</xsl:stylesheet>