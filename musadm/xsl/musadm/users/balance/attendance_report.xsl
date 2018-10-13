<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="root">

        <style>
            .attendance {
                margin-top: 40px;
                margin-bottom: 40px;
            }
        </style>

        <div class="attendance">
            <h3>История занятий</h3>

            <div class="balance-lessons table-responsive">
                <table class="table table-bordered">
                    <tr class="header">
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Статус</th>
                        <th>Преподаватель</th>
                    </tr>
                    <xsl:apply-templates select="schedule_lesson_report" />
                </table>
            </div>

            <p>Всего занятий за данный период: <xsl:value-of select="count(schedule_lesson_report)" /></p>
            <p>Из них явки/неявки:
                <xsl:value-of select="count(schedule_lesson_report[attendance = 1])" />
                <xsl:text>/</xsl:text>
                <xsl:value-of select="count(schedule_lesson_report[attendance = 0])" />
            </p>

        </div>
    </xsl:template>


    <xsl:template match="schedule_lesson_report">
        <tr>
            <td><xsl:value-of select="date" /></td>

            <td>
                <xsl:value-of select="time_from" />
                <xsl:text> - </xsl:text>
                <xsl:value-of select="time_to" />
            </td>

            <td>
                <xsl:choose>
                    <xsl:when test="attendance = 1">
                        Присутствовал(а)
                    </xsl:when>
                    <xsl:otherwise>
                        Отсутствовал(а)
                    </xsl:otherwise>
                </xsl:choose>
            </td>

            <td>
                <xsl:value-of select="surname" />
                <xsl:text> </xsl:text>
                <xsl:value-of select="name" />
            </td>
        </tr>
    </xsl:template>


</xsl:stylesheet>